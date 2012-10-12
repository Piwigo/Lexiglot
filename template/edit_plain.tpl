<form method="post" action="{$SELF_URL}" id="diffs">
<fieldset class="common">
  <legend>File content</legend>
  
  <textarea name="row_value" style="width:99.5%;height:{$AREA_HEIGHT}em;margin-bottom:10px;" tabindex="1">{$ROW_VALUE}</textarea>
  
{if $IS_TRANSLATOR}
  <div class="centered">
    <input type="hidden" name="key" value="{$SECRET_KEY}">
    <input type="submit" name="submit" value="Save" class="blue big" tabindex="2">
  </div>
{/if}

</fieldset>
</form>


{if $NB_LINES>40}
  <a href="#top" id="top-link" title="To top"></a>
  <a href="#bottom" id="bottom-link" title="To bottom"></a>
  
  {combine_script id="jquery.scrollTo" path="template/js/jquery.scrollTo.min.js" load="footer"}
  
  {footer_script}{literal}
  // smoothscroll
  $("#top-link")
    .click(function(e) {
      e.preventDefault();
      $.scrollTo("0%", 500);
    })
    .hover(
      function() { $(this).fadeTo(500, 0.70); },
      function() { $(this).fadeTo(500, 1.00); }
    );
  $("#bottom-link")
    .click(function(e) {
      e.preventDefault();
      $.scrollTo("100%", 500);
    })
    .hover(
      function() { $(this).fadeTo(500, 0.70); },
      function() { $(this).fadeTo(500, 1.00); }
    );
  {/literal}{/footer_script}
{/if}


{if $IS_TRANSLATOR}
  {footer_script}{literal}
  // check saves before close page
  var handlers = 0;
  $("textarea[name=\'row_value\']").change(function() {
    handlers++;
  });
  $("input[name=\'submit\']").click(function() {
    handlers = 0;
  });
  $(window).bind("beforeunload", function() {
    if (handlers > 0) return false;
  });
  {/literal}{/footer_script}
{/if}