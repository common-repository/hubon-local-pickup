<?php if (!defined('ABSPATH')) exit; ?>
<h1>HubOn Local Pickup</h1>
<p>HubOn shipping method for WooCommerce. Check our guide <a target="_blank" rel="noopener noreferrer" href="https://letshubon.com/accounts/integration">here</a>.</p>
<table class="form-table">
  <?php wp_nonce_field("hubon-settings", "hubon-nonce") ?>
  <?php $this->generate_settings_html(); ?>
  <tr>
    <th>
      <label><?php echo esc_html_e('Secret key', 'hubon-local-pickup') ?> <abbr class="required">*</abbr></label>
    </th>
    <td>
      <span>
        <textarea id="hubon_secret_key" name="hubon_secret_key" rows="5"><?php echo esc_textarea($options['hubon_secret_key']) ?></textarea>
      </span>
      <div>
        <div>
          <p id="licenceInfo" class="text-red-500"><?php echo esc_html($options["information_licence"] ?? "") ?></p>
        </div>
        <div class="mt-2">
          <button type="button" id="hubon-active-secret-key" class="wp-core-ui button-primary"><?php echo esc_html(empty($options["information_licence"]) && !empty($options['hubon_secret_key']) ? "Update Key" : "Activation") ?></button>
        </div>
        <p>Don't have a secret key? You can obtain one by visiting your
          account settings page <a target="_blank" rel="noopener noreferrer" href="https://letshubon.com/accounts/integration" class="text-blue-500 underline"> here</a></p>
      </div>
    </td>
    <!-- <td></td> -->
  </tr>
  <tr>
    <th>
      <label><?php echo esc_html_e('Display name', 'hubon-local-pickup') ?> <abbr class="required">*</abbr></label>
    </th>
    <td>
      <span>
        <input type="text" id="hubon_display_name" name="hubon_display_name" required placeholder="Input display name" value="<?php echo esc_attr($options['hubon_display_name']) ?>" readonly />
      </span>
    </td>
    <!-- <td></td> -->
  </tr>
</table>