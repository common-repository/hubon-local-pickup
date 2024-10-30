<?php if (!defined('ABSPATH')) exit; ?>
<div class="container py-4 mx-auto">
    <h1 class="text-xl">Hi, <?php echo esc_html(get_option("hubon_hubon_display_name")) ?></h1>
    <div class="w-full mt-6 bg-white rounded shadow-sm">
        <div class="flex flex-wrap">
            <?php
            $current_screen = get_current_screen();
            $to_be_paid = $current_screen->id === 'toplevel_page_hubon-to-be-paid' ? 'py-1 font-bold text-secondary' : 'font-semibold';
            $paid = $current_screen->id === 'hubon-pickup_page_hubon-paid' ? 'py-1 font-bold text-secondary' : 'font-semibold';
            $failed = $current_screen->id === 'hubon-pickup_page_hubon-failed' ? 'py-1 font-bold text-secondary' : 'font-semibold';

            ?>
            <div class="p-4">
                <a href="<?php echo esc_url(menu_page_url('hubon-to-be-paid', false)); ?>" class="hover:text-secondary-lighten <?php echo esc_attr($to_be_paid); ?>">
                    <?php esc_html_e('To be paid', 'hubon-local-pickup'); ?>
                </a>
            </div>
            <div class="p-4">
                <a href="<?php echo esc_url(menu_page_url('hubon-paid', false)); ?>" class="hover:text-secondary-lighten <?php echo esc_attr($paid); ?>">
                    <?php esc_html_e('Paid', 'hubon-local-pickup'); ?>
                </a>
            </div>
            <div class="p-4">
                <a href="<?php echo esc_url(menu_page_url('hubon-failed', false)); ?>" class="hover:text-secondary-lighten <?php echo esc_attr($failed); ?>">
                    <?php esc_html_e('Failed', 'hubon-local-pickup'); ?>
                </a>
            </div>
        </div>
    </div>

    <div id="hubon-react-admin" class="my-4"></div>
</div>