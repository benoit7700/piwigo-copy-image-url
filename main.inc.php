<?php
/*
Plugin Name: Copy Image URL
Version: 16.d
Description: Adds buttons on the public Piwigo photo page to copy the direct URL of the original image and an XL derivative.
Plugin URI: auto
Author: benoit7700
Author URI: https://github.com/benoit7700
Has Settings: false
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

define('CIU_ID', basename(dirname(__FILE__)));
define('CIU_PATH', PHPWG_PLUGINS_PATH . CIU_ID . '/');

if (!defined('IN_ADMIN'))
{
  add_event_handler('loc_end_picture', 'ciu_loc_end_picture', EVENT_HANDLER_PRIORITY_NEUTRAL);
}

function ciu_loc_end_picture()
{
  global $template, $picture;

  if (empty($picture['current']) || empty($picture['current']['path']))
  {
    return;
  }

  $current = $picture['current'];

  // Build the absolute URL of the original image using Piwigo's own URL helper.
  set_make_full_url();
  $original_url = get_element_url($current);
  unset_make_full_url();

  // Build an XL derivative URL when possible.
  $xl_url = '';
  try
  {
    $src = (isset($current['src_image']) && $current['src_image'] instanceof SrcImage)
      ? $current['src_image']
      : new SrcImage($current);

    $xl = DerivativeImage::get_one(IMG_XLARGE, $src);
    if ($xl)
    {
      $xl_url = $xl->get_url();
      if (!url_is_remote($xl_url))
      {
        $xl_url = get_absolute_root_url() . ltrim($xl_url, '/');
      }
    }
  }
  catch (Exception $e)
  {
    $xl_url = '';
  }

  // Add buttons through Piwigo's native toolbar extension point.
  // This is supported by the default theme and by Stripped.
  $original_js = json_encode($original_url, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
  $button_original =
    '<a href="#" title="Copier l\'URL directe de l\'image originale" '.
    'onclick="ciuCopyUrl('.htmlspecialchars($original_js, ENT_QUOTES, 'UTF-8').'); return false;">'.
    'URL originale</a>';

  $template->append('PLUGIN_PICTURE_BUTTONS', $button_original);

  if (!empty($xl_url))
  {
    $xl_js = json_encode($xl_url, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
    $button_xl =
      '<a href="#" title="Copier l\'URL de la taille XL" '.
      'onclick="ciuCopyUrl('.htmlspecialchars($xl_js, ENT_QUOTES, 'UTF-8').'); return false;">'.
      'URL XL</a>';

    $template->append('PLUGIN_PICTURE_BUTTONS', $button_xl);
  }

  // Inline JavaScript deliberately avoids the Clipboard API dependency on HTTPS.
  // If direct clipboard access is unavailable, a prompt displays the URL for manual copy.
  $template->append(
    'PLUGIN_PICTURE_BEFORE',
    '<script>
function ciuCopyUrl(url) {
  function showUrl() {
    window.prompt("URL directe de l\'image (Ctrl+C puis Entrée) :", url);
  }

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(function() {
      alert("URL copiée dans le presse-papiers.");
    }).catch(showUrl);
    return;
  }

  var ta = document.createElement("textarea");
  ta.value = url;
  ta.setAttribute("readonly", "");
  ta.style.position = "fixed";
  ta.style.left = "-9999px";
  document.body.appendChild(ta);
  ta.select();

  var ok = false;
  try { ok = document.execCommand("copy"); } catch (e) {}
  document.body.removeChild(ta);

  if (ok) {
    alert("URL copiée dans le presse-papiers.");
  } else {
    showUrl();
  }
}
</script>'
  );
}
?>