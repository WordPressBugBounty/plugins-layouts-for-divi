<?php 

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lfd-body">
    <div class="lfd-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('Layouts for Divi', 'layouts-for-divi'); ?></h1>
    </div>
    <div id="lfd-wrap" class="lfd-wrap">
        <div class="lfd-header">
            <div class="lfd-title lfd-is-inline"><h2 class="lfd-title"><?php esc_html_e('Divi Template Kits:', 'layouts-for-divi'); ?></h2></div>
            <div class="lfd-sync lfd-is-inline">
                <a href="javascript:void(0);" class="lfd-sync-btn"><?php esc_html_e('Sync Now', 'layouts-for-divi'); ?></a>
            </div>
        </div>
        <?php
        $lfd_categories = Layouts_Divi_Remote::lfd_get_instance()->categories_list();

        if (!empty($lfd_categories['category']) && $lfd_categories != "") {
            ?>
            <div class="collection-bar">
                <h4><?php esc_html_e('Browse by Industry', 'layouts-for-divi'); ?></h4>
                <ul class="collection-list">
                    <li><a class="lfd-category-filter active" data-filter="all" href="javascript:void(0)"><?php esc_html_e('All', 'layouts-for-divi'); ?></a></li>
                    <?php
                    foreach ($lfd_categories['category'] as $cat) {
                        ?>
                        <li><a href="javascript:void(0);" class="lfd-category-filter" data-filter="<?php echo esc_attr($cat['slug']); ?>" ><?php echo esc_attr($cat['title']); ?></a></li>
                        <?php
                    }
                    ?>
                </ul>
            </div>
            <?php
        }
        ?>

        <div class="lfd_wrapper">
            <?php
            $lfd_data = Layouts_Divi_Remote::lfd_get_instance()->templates_list();
            $lfd_i = 0;
            if (!empty($lfd_data['templates']) && $lfd_data !== "") {
                foreach ($lfd_data['templates'] as $lfd_key => $lfd_val) {
                    $lfd_categories = "";
                    foreach ($lfd_val['category'] as $lfd_ckey => $lfd_cval) {
                        $lfd_categories .= sanitize_title($lfd_cval) . " ";
                    }
                    ?>
                    <div class="lfd_box lfd_filter <?php echo esc_attr($lfd_categories); ?>">
                        <div class="lfd_box_widget">
                            <div class="lfd-media">
                                <img src="<?php echo esc_url($lfd_val['thumbnail']); ?>" alt="screen 1">
                                <?php if ($lfd_val['is_premium'] == true) { ?>
                                    <span class="pro-btn"><?php echo esc_html__('PRO', 'layouts-for-divi'); ?></span>
                                <?php } else { ?>
                                    <span class="free-btn"><?php echo esc_html__('FREE', 'layouts-for-divi'); ?></span>
                                <?php } ?>
                            </div>
                            <div class="lfd-template-title"><?php echo esc_html($lfd_val['title'], 'layouts-for-divi'); ?></div>
                            <div class="lfd-btn">
                                <a href="javascript:void(0);" data-url="<?php echo esc_url($lfd_val['url']); ?>" title="<?php echo esc_html__('Preview', 'layouts-for-divi'); ?>" class="btn pre-btn previewbtn"><?php echo esc_html__('Preview', 'layouts-for-divi'); ?></a>
                                <a href="javascript:void(0);" title="<?php echo esc_html__('Install', 'layouts-for-divi'); ?>" class="btn ins-btn installbtn"><?php echo esc_html__('Install', 'layouts-for-divi'); ?></a>
                            </div>
                        </div>
                    </div>

                    <!-- Preview popup div start -->
                    <div class="lfd-preview-popup" id="preview-in-<?php echo esc_attr($lfd_i); ?>">
                        <div class="lfd-preview-container">
                            <div class="lfd-preview-header">
                                <div class="lfd-preview-title"><?php echo esc_attr($lfd_val['title']); ?></div>
                                <?php if ($lfd_val['is_premium'] == true) { ?>
                                    <div class="lfd-buy">
                                        <p class="lfd-buy-msg"><?php esc_html_e('This template is premium version', 'layouts-for-divi'); ?></p>
                                        <span class="lfd-buy-loader"></span>

                                        <a href="javascript:void(0);" class="btn ins-btn lfd-buy-btn" disabled data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Buy Now', 'layouts-for-divi'); ?></a>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-buy-template" style="display:none" target="_blank"><?php esc_html_e('Edit Template', 'layouts-for-divi'); ?></a>
                                    </div>
                                <?php } else { ?>
                                    <div class="lfd-import">
                                        <p class="lfd-msg"><?php esc_html_e('Import this template via one click', 'layouts-for-divi'); ?></p>
                                        <span class="lfd-loader"></span>

                                        <a href="javascript:void(0);" class="btn ins-btn lfd-import-btn" disabled data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Import Template', 'layouts-for-divi'); ?></a>
                                        <a href="#" class="btn ins-btn lfd-edit-template" style="display:none" target="_blank"><?php esc_html_e('Edit Template', 'layouts-for-divi'); ?></a>
                                    </div>

                                    <span><?php esc_html_e('OR', 'layouts-for-divi'); ?></span>

                                    <div class="lfd-import lfd-page-create">
                                        <p><?php esc_html_e('Create a new page from this template', 'layouts-for-divi'); ?></p>
                                        <input type="text" class="lfd-page-name-<?php echo esc_attr($lfd_val['id']); ?>" placeholder="Enter a Page Name" />
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-create-page-btn" data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Create New Page', 'layouts-for-divi'); ?></a>
                                    </div>

                                    <span class="lfd-loader-page"></span>

                                    <div class="lfd-import lfd-page-edit" style="display:none" >
                                        <p><?php esc_html_e('Your template is successfully imported!', 'layouts-for-divi'); ?></p>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-edit-page" target="_blank" ><?php esc_html_e('Edit Page', 'layouts-for-divi'); ?></a>
                                    </div>
                                    <div class="lfd-import lfd-page-error" style="display:none" >
                                        <p class="lfd-error"><?php esc_html_e('Something went wrong!', 'layouts-for-divi'); ?></p>
                                    </div>
                                <?php } ?>
                                <span class="lfd-close-icon"></span>

                                <a href="<?php echo esc_url($lfd_val['url']); ?>" class="lfd-dashicons-link" title="<?php esc_html_e('Open Preview in New Tab', 'layouts-for-divi'); ?>" rel="noopener noreferrer" target="_blank">
                                    <span class="lfd-dashicons"></span>
                                </a>
                            </div>
                            <iframe width="100%" height="100%" src=""></iframe>
                        </div>
                    </div>
                    <!-- Preview popup div end -->

                    <!-- Install popup div start -->
                    <div class="lfd-install-popup" id="content-in-<?php echo esc_attr($lfd_i); ?>">
                        <div class="lfd-container">
                            <div class="lfd-install-header">
                                <div class="lfd-install-title"><?php echo esc_attr($lfd_val['title']); ?></div>
                                <span class="lfd-close-icon"></span>
                            </div>
                            <div class="lfd-install-content">

                                <?php if ($lfd_val['is_premium'] == true) { ?>
                                    <p class="lfd-msg"><?php esc_html_e('This template is premium version', 'layouts-for-divi'); ?></p>
                                    <div class="lfd-btn">
                                        <span class="lfd-loader"></span>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-buy-btn" data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Buy Now', 'layouts-for-divi'); ?></a>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-buy-template" style="display:none" target="_blank"><?php esc_html_e('Edit Template', 'layouts-for-divi'); ?></a>
                                    </div>

                                <?php } else { ?>

                                    <p class="lfd-msg"><?php esc_html_e('Import this template via one click', 'layouts-for-divi'); ?></p>
                                    <div class="lfd-btn">
                                        <span class="lfd-loader"></span>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-import-btn" data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Import Template', 'layouts-for-divi'); ?></a>
                                        <a href="javascript:void(0);" class="btn ins-btn lfd-edit-template" style="display:none" target="_blank"><?php esc_html_e('Edit Template', 'layouts-for-divi'); ?></a>
                                    </div>

                                    <p class="lfd-horizontal"><?php esc_html_e('OR', 'layouts-for-divi'); ?></p>

                                    <div class="lfd-page-create">
                                        <p><?php esc_html_e('Create a new page from this template', 'layouts-for-divi'); ?></p>
                                        <input type="text" class="lfd-page-<?php echo esc_attr($lfd_val['id']); ?>" placeholder="Enter a Page Name" />
                                        <div class="lfd-btn">
                                            <a href="javascript:void(0);" style="padding: 0;" class="btn pre-btn lfd-create-page-btn" data-name="crtbtn" data-template-id="<?php echo esc_attr($lfd_val['id']); ?>" ><?php esc_html_e('Create New Page', 'layouts-for-divi'); ?></a>
                                            <span class="lfd-loader-page"></span>
                                        </div>
                                    </div>
                                    <div class="lfd-create-div lfd-page-edit" style="display:none" >
                                        <p style="color: #000;"><?php esc_html_e('Your page is successfully imported!', 'layouts-for-divi'); ?></p>
                                        <div class="lfd-btn">
                                            <a href="javascript:void(0);" class="btn pre-btn lfd-edit-page" target="_blank" ><?php esc_html_e('Edit Page', 'layouts-for-divi'); ?></a>
                                        </div>
                                    </div>
                                    <div class="lfd-import lfd-page-error" style="display:none;" >
                                        <p class="lfd-error" style="color: #444;"><?php esc_html_e('Something went wrong!', 'layouts-for-divi'); ?></p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <!-- Install popup div end -->
                    <?php
                    $lfd_i++;
                }
            } else {
                echo esc_html($lfd_data['message']);
            }
            ?>
        </div>
    </div>
</div>
