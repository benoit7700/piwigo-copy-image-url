<?php
/*
Plugin Name: Copy Image URL
Version: 16.e
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
#ciuExifBlock{margin-top:12px}
#ciuExifBlock .ciuRow{margin:6px 0}
#ciuExifBlock button{
  padding:4px 8px;
  margin:2px 4px 2px 0;
  cursor:pointer;
  font-size:.9em
}
#ciuExifBlock .ciuValue{
  display:block;
  margin-top:4px;
  font-size:.85em;
  word-break:break-all;
  opacity:.85
}
</style>

<dl id="ciuExifBlock" class="imageInfoTable">
  <h3>Liens image</h3>
  <div class="imageInfo ciuRow">
    <dt>Originale</dt>
    <dd>
      <button type="button" class="ciu-copy" data-url="{$CIU_ORIGINAL_URL|escape:'html'}">Copier URL originale</button>
      <span class="ciuValue">{$CIU_ORIGINAL_URL|escape:'html'}</span>
    </dd>
  </div>
  {if !empty($CIU_XL_URL)}
  <div class="imageInfo ciuRow">
    <dt>XL</dt>
    <dd>
      <button type="button" class="ciu-copy" data-url="{$CIU_XL_URL|escape:'html'}">Copier URL XL</button>
      <span class="ciuValue">{$CIU_XL_URL|escape:'html'}</span>
    </dd>
  </div>
  {/if}
</dl>

<script>
(function(){
  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly','');
    ta.style.position='fixed';
    ta.style.left='-9999px';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    if (!ok) window.prompt("URL directe de l'image :", text);
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.ciu-copy');
    if (!btn) return;
    var url = btn.getAttribute('data-url');
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).catch(function(){ fallbackCopy(url); });
    } else {
      fallbackCopy(url);
    }
  });
})();
</script>
{/if}
TPL;

  // Preferred placement for default-like themes: just before metadata/EXIF.
  if (strpos($content, '{if isset($metadata)}') !== false)
  {
    $content = str_replace('{if isset($metadata)}', $block . "
{if isset($metadata)}", $content);
    return $content;
  }

  // Stripped theme: insert before the metadata tabs section if present.
  $marker = "{if isset($metadata)}
					{foreach from=$metadata item=meta key=id}";
  if (strpos($content, $marker) !== false)
  {
    return str_replace($marker, $block . "
" . $marker, $content);
  }

  // Fallback: append at end of the photo template.
  return $content . "
" . $block;
}
?>