  <form name="save_form" method="POST" action="<?php echo $options_url; ?>">
    <?php wp_nonce_field(self::nonce); ?>

    <p>
      <?php _e('Please enter valid paths to the external tools and please be sure that all of them are installed.', self::ld); ?>
    </p>

    <p>   
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="wpsm_exiftool_path"><?php esc_html_e('Exiftool path', self::ld); ?></label>
          </th>
          <td>
            <input type="text" class="wpsm_input_path" name="wpsm_exiftool_path" id="wpsm_exiftool_path" value="<?php echo esc_attr($tools['exiftool']['path']); ?>" />
            <?php
            if ($tools['exiftool']['valid'])
            {
              echo '<span class="wpsm_text_valid">'.__('Valid', self::ld).'</span>';
            }
            else
            {
              echo '<span class="wpsm_text_invalid">'.__('Invalid', self::ld).'</span>';            
            }
            ?>
          </td>
        </tr>          

        <tr>
          <th scope="row">
            <label for="wpsm_convert_path"><?php esc_html_e('Convert path', self::ld); ?></label>
          </th>
          <td>
            <input type="text" class="wpsm_input_path" name="wpsm_convert_path" id="wpsm_convert_path" value="<?php echo esc_attr($tools['convert']['path']); ?>" />
            <?php
            if ($tools['convert']['valid'])
            {
              echo '<span class="wpsm_text_valid">'.__('Valid', self::ld).'</span>';
            }
            else
            {
              echo '<span class="wpsm_text_invalid">'.__('Invalid', self::ld).'</span>';            
            }
            ?>
          </td>
        </tr>          

        <tr>
          <th scope="row">
            <label for="wpsm_composite_path"><?php esc_html_e('Composite path', self::ld); ?></label>
          </th>
          <td>
            <input type="text" class="wpsm_input_path" name="wpsm_composite_path" id="wpsm_composite_path" value="<?php echo esc_attr($tools['composite']['path']); ?>" />
            <?php
            if ($tools['composite']['valid'])
            {
              echo '<span class="wpsm_text_valid">'.__('Valid', self::ld).'</span>';
            }
            else
            {
              echo '<span class="wpsm_text_invalid">'.__('Invalid', self::ld).'</span>';            
            }
            ?>
          </td>
        </tr>          
      </table>
    </p>
    
    <?php
    $safe_mode = ini_get('safe_mode');
    if ($safe_mode)
    {
    ?>
      <b><?php _e('Warning! Option "safe_mode" is enabled and must be disabled.', self::ld); ?></b>
    <?php
    }
    ?>
        
    
    <?php submit_button(__('Save Changes', self::ld), 'primary', 'save_changes', true); ?>
  </form>
</div>