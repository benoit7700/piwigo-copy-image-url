<?php
/*
Plugin Name: Copy Image URL
Version: 16.a
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

  // Build absolute URLs with Piwigo's own URL helpers.
  set_make_full_url();
  $original_url = get_element_url($current);

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
    }
  }
  catch (Exception $e)
  {
    $xl_url = '';
  }

  unset_make_full_url();

  if (!empty($xl_url) && !url_is_remote($xl_url))
  {
    $xl_url = get_absolute_root_url() . ltrim($xl_url, '/');
  }

  $template->assign(array(
    'CIU_ORIGINAL_URL' => $original_url,
    'CIU_XL_URL' => $xl_url,
  ));

  $template->set_prefilter('picture', 'ciu_picture_prefilter');
}

function ciu_picture_prefilter($content)
{
  $block = <<<'TPL'
{if isset($CIU_ORIGINAL_URL)}
<style>
#ciu-url-tools{margin:14px auto;padding:12px 14px;max-width:980px;border:1px solid rgba(128,128,128,.35);border-radius:8px;text-align:center}
#ciu-url-tools .ciu-title{font-weight:600;margin-bottom:8px}
#ciu-url-tools button{margin:3px 5px;padding:7px 12px;cursor:pointer}
#ciu-url-tools .ciu-url{display:block;margin:8px auto 0;max-width:900px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.9em;opacity:.85}
#ciu-url-tools .ciu-status{margin-left:6px;font-size:.9em}
</style>
<div id="ciu-url-tools">
  <div class="ciu-title">URL de l'image</div>
  <button type="button" class="ciu-copy" data-url="{$CIU_ORIGINAL_URL|escape:'html'}">Copier URL originale</button>
  {if !empty($CIU_XL_URL)}
    <button type="button" class="ciu-copy" data-url="{$CIU_XL_URL|escape:'html'}">Copier URL XL</button>
  {/if}
  <span class="ciu-status" aria-live="polite"></span>
  <span class="ciu-url">{$CIU_ORIGINAL_URL|escape:'html'}</span>
</div>
<script>
(function(){
  var box = document.getElementById('ciu-url-tools');
  if (!box) return;
  var status = box.querySelector('.ciu-status');

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly','');
    ta.style.position='fixed';
    ta.style.opacity='0';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    return ok;
  }

  box.addEventListener('click', function(e){
    var btn = e.target.closest('.ciu-copy');
    if (!btn) return;
    var url = btn.getAttribute('data-url');
    var done = function(ok){
      status.textContent = ok ? 'URL copiée' : 'Copie impossible';
      setTimeout(function(){ status.textContent=''; }, 1800);
    };

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url)
        .then(function(){ done(true); })
        .catch(function(){ done(fallbackCopy(url)); });
    } else {
      done(fallbackCopy(url));
    }
  });
})();
</script>
{/if}
TPL;

  return $content . "\n" . $block;
}
?>