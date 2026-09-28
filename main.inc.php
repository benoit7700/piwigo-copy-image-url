<?php
/*
Plugin Name: Copy Image URL
Version: 16.g
Description: Adds direct image URL tools on the public Piwigo photo page, including original and XL URLs.
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

  set_make_full_url();
  $original_url = get_element_url($current);
  unset_make_full_url();

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

  $original_js = json_encode($original_url, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
  $xl_js = json_encode($xl_url, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);

  $html = '<script>
(function(){
  var originalUrl = '.$original_js.';
  var xlUrl = '.$xl_js.';

  function esc(s) {
    return String(s).replace(/[&<>"\']/g, function(c) {
      return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\'":"&#039;"}[c];
    });
  }

  function copyUrl(url) {
    function fallback() {
      var ta = document.createElement("textarea");
      ta.value = url;
      ta.setAttribute("readonly", "");
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      var ok = false;
      try { ok = document.execCommand("copy"); } catch(e) {}
      document.body.removeChild(ta);
      if (!ok) window.prompt("URL directe de l\'image :", url);
    }

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).catch(fallback);
    } else {
      fallback();
    }
  }

  function install() {
    if (document.getElementById("ciu-links")) return;

    var host = document.getElementById("imageInfos");
    if (!host) {
      host = document.querySelector("#Tinfo .tabBlockContent") || document.getElementById("tabZone");
    }
    if (!host) return;

    var box = document.createElement("div");
    box.id = "ciu-links";
    box.style.marginTop = "14px";
    box.style.paddingTop = "10px";
    box.style.borderTop = "1px solid rgba(180,180,180,.25)";

    var html = "<h3 style=\"margin:0 0 8px 0\">Liens image</h3>";
    html += "<div style=\"margin-bottom:8px\"><strong>Originale</strong><br>";
    html += "<button type=\"button\" id=\"ciu-copy-original\" style=\"margin:4px 0;padding:4px 8px;cursor:pointer\">Copier URL originale</button>";
    html += "<div style=\"font-size:.85em;word-break:break-all;opacity:.8\">"+esc(originalUrl)+"</div></div>";

    if (xlUrl) {
      html += "<div><strong>XL</strong><br>";
      html += "<button type=\"button\" id=\"ciu-copy-xl\" style=\"margin:4px 0;padding:4px 8px;cursor:pointer\">Copier URL XL</button>";
      html += "<div style=\"font-size:.85em;word-break:break-all;opacity:.8\">"+esc(xlUrl)+"</div></div>";
    }

    box.innerHTML = html;

    var metadata = document.getElementById("Metadata");
    if (metadata && metadata.parentNode === host) {
      host.insertBefore(box, metadata);
    } else {
      host.appendChild(box);
    }

    document.getElementById("ciu-copy-original").onclick = function(){ copyUrl(originalUrl); };
    if (xlUrl && document.getElementById("ciu-copy-xl")) {
      document.getElementById("ciu-copy-xl").onclick = function(){ copyUrl(xlUrl); };
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", install);
  } else {
    install();
  }
})();
</script>';

  // PLUGIN_PICTURE_AFTER is rendered as a scalar in Piwigo themes.
  // Using append() turns it into an array and causes "Array to string conversion".
  $existing = $template->get_template_vars('PLUGIN_PICTURE_AFTER');
  if (is_array($existing))
  {
    $existing = implode("\n", $existing);
  }
  if (!is_string($existing))
  {
    $existing = '';
  }

  $template->assign('PLUGIN_PICTURE_AFTER', $existing . $html);
}
?>