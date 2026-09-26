<?php
/**
 * Imports a remote Divi layout as a page or a Divi Library item.
 *
 * @package Layouts_For_Divi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for importing a template.
 *
 * The remote API returns Divi shortcode markup. Before it is saved, every
 * remote image it references (src / image_url / background_image URLs and
 * gallery_ids attachment IDs) is sideloaded into the local Media Library
 * and the markup is rewritten to point at the local copy.
 */
class Layouts_Divi_Importer {

	/**
	 * Remote-to-local media map for the current import, so an image used
	 * several times in one layout is only looked up / downloaded once.
	 *
	 * Keys are remote URLs or remote attachment IDs; values are the local
	 * URL or local attachment ID that replaces them.
	 *
	 * @var array<string, string|int>
	 */
	private $media_map = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * The action name is plugin-prefixed: the sibling Layouts for Elementor
	 * plugin registers the generic wp_ajax_handle_import, and with both
	 * plugins active the first-loaded handler would answer both plugins'
	 * requests.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_lfd_handle_import', array( $this, 'handle_import' ) );
	}

	/**
	 * AJAX handler: import a template.
	 *
	 * Prints the new post ID on success, or an error message on failure.
	 *
	 * @return void
	 */
	public function handle_import() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), Layouts_For_Divi::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'layouts-for-divi' ), '', array( 'response' => 403 ) );
		}

		// Same capability as the "Layouts" screen itself: an import creates
		// published Divi Library items / pages from external content and
		// sideloads media, so it is not something Contributors (edit_posts)
		// should be able to trigger.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'layouts-for-divi' ), '', array( 'response' => 403 ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		$with_page   = isset( $_POST['with_page'] ) ? sanitize_text_field( wp_unslash( $_POST['with_page'] ) ) : '';

		$template = Layouts_Divi_Remote::lfd_get_instance()->get_template_content( $template_id );

		if ( is_wp_error( $template ) ) {
			echo esc_html( $template->get_error_message() );
			wp_die();
		}

		// A string here is a message from the API, e.g. "Record not found.".
		if ( is_string( $template ) ) {
			echo esc_html( $template );
			wp_die();
		}

		$post_id = $this->create_page( $template, $with_page );
		if ( is_wp_error( $post_id ) ) {
			echo esc_html( $post_id->get_error_message() );
		} else {
			echo (int) $post_id;
		}
		wp_die();
	}

	/**
	 * Save the template as a new draft page, or as a Divi Library layout.
	 *
	 * @param array  $template  Template data from the API (title, template).
	 * @param string $with_page Page title for a new page, or empty for a Divi Library import.
	 * @return int|\WP_Error Post ID on success.
	 */
	private function create_page( $template, $with_page ) {

		if ( empty( $template['template'] ) || ! is_string( $template['template'] ) ) {
			return new \WP_Error( 'template_data_error', __( 'An invalid data was returned.', 'layouts-for-divi' ) );
		}

		$this->media_map = array();
		$content         = $this->localize_media( $template['template'] );
		$title           = sanitize_text_field( isset( $template['title'] ) ? $template['title'] : '' );

		if ( '' !== $with_page ) {
			return wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_title'   => sanitize_text_field( $with_page ),
					'post_content' => $content,
					'post_status'  => 'draft',
					'meta_input'   => array(
						'_et_pb_use_builder' => 'on',
					),
				),
				true
			);
		}

		// Re-importing the same layout updates the existing library item
		// instead of creating a duplicate.
		$existing = get_page_by_path( sanitize_title( $title ), OBJECT, 'et_pb_layout' );

		if ( $existing instanceof WP_Post ) {
			return wp_update_post(
				array(
					'ID'           => $existing->ID,
					'post_title'   => $title,
					'post_content' => $content,
				),
				true
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'et_pb_layout',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'meta_input'   => array(
					'_et_pb_use_builder'         => 'on',
					'_et_pb_built_for_post_type' => 'page',
				),
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			wp_set_object_terms( $post_id, 'layout', 'layout_type', true );
		}

		return $post_id;
	}

	/**
	 * Rewrite every remote image reference in the markup to a local copy.
	 *
	 * Matches any attribute ending in src, image_url or background_image
	 * (e.g. src, logo_image_url) whose value is an absolute http(s) URL,
	 * and any gallery_ids attribute. A reference that cannot be localized
	 * is left pointing at the original remote value.
	 *
	 * @param string $content Divi shortcode markup.
	 * @return string
	 */
	private function localize_media( $content ) {
		$content = (string) preg_replace_callback(
			'/(src|image_url|background_image)="(https?:\/\/[^"]+)"/i',
			function ( $matches ) {
				$local = $this->lfd_get_local_url( $matches[2] );
				return $matches[1] . '="' . ( '' !== $local ? $local : $matches[2] ) . '"';
			},
			$content
		);

		return (string) preg_replace_callback(
			'/gallery_ids="([0-9,\s]+)"/i',
			function ( $matches ) {
				$ids = array();
				foreach ( explode( ',', $matches[1] ) as $remote_id ) {
					$remote_id = trim( $remote_id );
					if ( '' === $remote_id ) {
						continue;
					}
					$local_id = $this->lfd_get_local_attachment_id( (int) $remote_id );
					$ids[]    = $local_id ? $local_id : $remote_id;
				}
				return 'gallery_ids="' . implode( ',', $ids ) . '"';
			},
			$content
		);
	}

	/**
	 * Get the local URL for a remote image URL, sideloading it if needed.
	 *
	 * @param string $remote_url Remote image URL.
	 * @return string Local URL, or empty string on failure.
	 */
	private function lfd_get_local_url( $remote_url ) {
		if ( isset( $this->media_map[ $remote_url ] ) ) {
			return (string) $this->media_map[ $remote_url ];
		}

		$local_url = $this->lfd_find_existing_attachment_url( $remote_url );
		if ( '' === $local_url ) {
			$attach_id = $this->lfd_insert_media_from_url( $remote_url );
			$local_url = $attach_id ? (string) wp_get_attachment_url( $attach_id ) : '';
		}

		$this->media_map[ $remote_url ] = $local_url;
		return $local_url;
	}

	/**
	 * Get the local attachment ID for a remote attachment ID, sideloading it if needed.
	 *
	 * @param int $remote_id Remote attachment ID.
	 * @return int Local attachment ID, or 0 on failure.
	 */
	private function lfd_get_local_attachment_id( $remote_id ) {
		$key = 'id:' . $remote_id;
		if ( isset( $this->media_map[ $key ] ) ) {
			return (int) $this->media_map[ $key ];
		}

		$local_id   = 0;
		$remote_url = $this->lfd_get_image_url( $remote_id );
		if ( '' !== $remote_url ) {
			$existing_url = $this->lfd_find_existing_attachment_url( $remote_url );
			$local_id     = '' !== $existing_url ? attachment_url_to_postid( $existing_url ) : $this->lfd_insert_media_from_url( $remote_url );
		}

		$this->media_map[ $key ] = $local_id;
		return $local_id;
	}

	/**
	 * Find an already-imported copy of a remote image in the local Media Library.
	 *
	 * Matches by attachment slug (the file name without extension), also
	 * trying the "-1" / "-2" suffixes WordPress adds to duplicate slugs.
	 *
	 * @param string $image_url Remote image URL.
	 * @return string Local attachment URL, or empty string if none.
	 */
	private function lfd_find_existing_attachment_url( $image_url ) {
		$path      = (string) wp_parse_url( $image_url, PHP_URL_PATH );
		$file_name = sanitize_title( pathinfo( $path, PATHINFO_FILENAME ) );
		if ( '' === $file_name ) {
			return '';
		}

		foreach ( array( '', '-1', '-2' ) as $suffix ) {
			$url = $this->lfd_get_attachment_url_by_slug( $file_name . $suffix );
			if ( '' !== $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Get a remote image URL by remote attachment ID.
	 *
	 * @param int $img_id Remote attachment ID.
	 * @return string Image URL, or empty string if unavailable.
	 */
	private function lfd_get_image_url( $img_id ) {
		$image_url = Layouts_Divi_Remote::lfd_get_instance()->get_media_image( $img_id );
		if ( is_string( $image_url ) && 0 === strpos( $image_url, 'http' ) ) {
			return $image_url;
		}
		return '';
	}

	/**
	 * Get a local attachment's URL by its slug.
	 *
	 * @param string $slug Attachment slug.
	 * @return string Attachment URL, or empty string if none.
	 */
	private function lfd_get_attachment_url_by_slug( $slug ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'inherit',
			)
		);

		if ( empty( $attachments ) ) {
			return '';
		}

		return (string) wp_get_attachment_url( $attachments[0]->ID );
	}

	/**
	 * Download a remote image into the Media Library.
	 *
	 * Uses wp_safe_remote_get() (which refuses private/loopback hosts), and
	 * only accepts files whose extension WordPress maps to an image MIME
	 * type, so markup from the API can't be used to pull arbitrary files
	 * into the uploads directory.
	 *
	 * @param string $image_url Remote image URL.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private function lfd_insert_media_from_url( $image_url ) {
		$filename = sanitize_file_name( wp_basename( (string) wp_parse_url( $image_url, PHP_URL_PATH ) ) );
		$filetype = wp_check_filetype( $filename );
		if ( '' === $filename || empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
			return 0;
		}

		$response = wp_safe_remote_get( $image_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}

		$upload_file = wp_upload_bits( $filename, null, wp_remote_retrieve_body( $response ) );
		if ( ! empty( $upload_file['error'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload_file['file'],
			0,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] ) );

		return $attachment_id;
	}
}

new Layouts_Divi_Importer();
