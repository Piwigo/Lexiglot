<div class="pagination">{$PAGINATION}</div>

<div id="display_buttons">
  <a href="{$DISPLAY.url_all}" class="all {if $DISPLAY.mode=='all'}active display{/if}">All</a>
  <a href="{$DISPLAY.url_missing}" class="missing {if $DISPLAY.mode=='missing'}active display{/if}">Untranslated</a>
  <a href="#" class="search {if $DISPLAY.mode=='search'}active display{/if}">Search</a>
</div>

<div style="clear:both;"></div>

<form method="post" action="{$SEARCH.url}" id="diffs_search" {if $DISPLAY.mode!='search'}style="display:none;"{/if}>
<fieldset class="common">
  <input type="text" name="needle" size="50" value="{$SEARCH.needle}">
  &nbsp;&nbsp;Where ? 
    <label><input type="radio" name="where" value="row_value" {if $SEARCH.where=='row_value'}checked="checked"{/if}> Translations</label> 
    <label><input type="radio" name="where" value="original" {if $SEARCH.where=='original'}checked="checked"{/if}> Reference</label>
  &nbsp;&nbsp;<input type="submit" name="search" value="Search" class="blue">
</fieldset>
</form>


<form method="post" action="{$SELF_URL}" id="diffs" style="margin-top:10px;">
<fieldset class="common">
  <legend></legend>
  
  <table class="common">
  {if count($DIFFS)>0 and count($reference_languages)>1}
    <tr>
      <th colspan="3" style="text-align:left;padding-bottom:5px;">
        Change reference language to
        <select onchange="document.location = this.options[this.selectedIndex].value;">
        {foreach from=$reference_languages item=row}
          <option value="{$row.switch_url}" {if $row.selected}selected="selected"{assign var=dl value=$row.name}{/if} 
            {if $row.id == $CONF.default_language}style="font-weight:bold;background:#222;color:#eee;border-radius:0.6em;"{/if}
            >{$row.name}</option>
        {/foreach}
        </select>
        
        {if $DISPLAY_REFERENCE_WARNING}
          {ui_message type="highlight" icon="info" style="display:inline-block;padding:0.3em;font-weight:normal;margin:0;" 
            content="Not using the default language ("|cat:$dl|cat:") as reference may provide incomplete translation table."}
        {/if}
      </th>
    </tr>
  {/if}
  
  {foreach from=$DIFFS item=row}
    {assign var=i value=$row.i}
    
    <tr class="main {if $i is odd}odd{else}even{/if} {$row.STATUS} {if $row.error}error{/if}">
      <td class="original"><pre>{$row.ORIGINAL}</pre></td>
      
      <td class="translation">
        <textarea name="rows[{$i}][row_name]" style="display:none;">{$row.KEY}</textarea>
        {$row.FIELD}
      </td>
      
      <td class="details">
        <a href="#" class="expand tiptip" title="Show details" data="{$i}"><img src="template/images/magnifier_zoom_in.png" alt="[+]"></a>
        {if $IS_TRANSLATOR}<a href="#" class="save tiptip" title="Save this string" data="{$i}"><img src="template/images/disk.png" alt="save"></a>{/if}
      </td>
    </tr>
    
    <tr class="details {if $i is odd}odd{else}even{/if} {$row.STATUS}" style="display:none;">
      <td class="original">
        <h5>String identifier :</h5>
        <pre>{$row.KEY}</pre>
      </td>
      <td class="translation"></td>
      <td class="details"></td>
    </tr>
  {/foreach}
  </table>
  
  {if count($DIFFS)==0}
  {if $DISPLAY.mode=='search'}
    {ui_message type="warning" icon="alert" content="No result"}
  {else}
    {ui_message type="warning" icon="alert" content="Translation complete"}
  {/if}
  {/if}
  
  
{if count($DIFFS)>=20}
  <div class="pagination">{$PAGINATION}</div>
{/if}

{if $IS_TRANSLATOR and count($DIFFS)!=0}
  <div class="centered">
    <input type="hidden" name="key" value="{$SECRET_KEY}">
    <input type="submit" name="submit" class="blue big" value="Save all" tabindex="{$i+1}">
  </div>
{/if}

</fieldset>
</form>


{if count($_DIFFS)>0}
<table class="legend">
  <tr>
    <td><span>&nbsp;</span> Up-to-date strings</td>
    <td><span class="missing">&nbsp;</span> Untranslated strings</td>
    <td><span class="new">&nbsp;</span> Newly translated strings</td>
  </tr>
</table>
{/if}

{if $PROGRESS_BAR}
  {ui_message type="highlight" icon="signal" content="<b>File progression :</b> "|cat:$PROGRESS_BAR}
{/if}


{combine_script id="functions" path="template/js/functions.js" load="footer"}
{combine_script id="jquery.tiptip" path="template/js/jquery.tiptip.min.js" load="footer"}
{combine_css path="template/js/jquery.tiptip.css"}

{footer_script}{literal}
// tiptip
$(".tiptip").tipTip({ 
  delay:200,
  defaultPosition:"right"
});

// linked table rows follow hover state
$("tr.main").hover(
  function() { $(this).next("tr").addClass("hover"); },
  function() { $(this).next("tr").removeClass("hover"); }
);
$("tr.details").hover(
  function() { $(this).prev("tr").addClass("hover"); },
  function() { $(this).prev("tr").removeClass("hover"); }
);

// toggle search form
$("#display_buttons a.search").click(function() {
  if ("{/literal}{$DISPLAY.mode}{literal}" != "search") {
    if ($(this).hasClass("active")) {
      $("#display_buttons a").removeClass("active");
      $("#display_buttons a.display").addClass("active");
      $("#diffs_search").hide("slow");
    }
    else {
      $("#display_buttons a").removeClass("active");
      $(this).addClass("active");
      $("#diffs_search").show("slow");
    }
  }
  return false;
});

$("#display_buttons a:not(.search)").click(function() {
  if ($(this).hasClass("{/literal}{$DISPLAY.mode}{literal}")) {
    $("#display_buttons a").removeClass("active");
    $(this).addClass("active");
    $("#diffs_search").hide("slow");
    return false;
  }
  else {
    return true;
  }
});

// perform ajax request for string details
$("a.expand").click(function() {
  $trigger = $(this);
  $details_row = $(this).parents("tr.main").next("tr.details");
  
  if ($details_row.css("display") == "none") {
    $("a.expand img").attr("src", "template/images/magnifier_zoom_in.png");
    $("tr.details").hide();
    
    $trigger.children("img").attr("src", "template/images/magnifier_zoom_out.png");
    $details_row.show();
    
    $container = $details_row.children("td.translation");
    if ($container.hasClass("loaded") == false)
    {
      row_name = $("textarea[name='rows["+ $(this).attr("data") +"][row_name]']").val();
      $container.html("<p><img src='template/images/load16.gif'> <i>Loading...</i></p>");
    
      $.ajax({
        type: "POST",
        url: "ajax.php",
        data: {{/literal} "action":"row_log", "project":"{$PROJECT}", "language":"{$LANGUAGE}", "file":"{$FILE}", "key":"{$SECRET_KEY}", "row_name": utf8_encode(row_name) {literal}}
      }).done(function(msg) {
        msg = $.parseJSON(msg);
        if (msg.errcode == "success") {
          $container.addClass("loaded").html("<p>"+ msg.data +"</p>");
        }
        else {
          overlayMessage(msg.data, msg.errcode, $trigger);
        }
      });
    }
  }
  else {
    $trigger.children("img").attr("src", "template/images/magnifier_zoom_in.png");
    $details_row.hide();
  }
  
  return false;
});
{/literal}{/footer_script}

{if $IS_TRANSLATOR}
  {footer_script}{literal}
  // check saves before close page
  var handlers = 0;
  $("textarea[name$='[row_value]']").change(function() {
    handlers++;
  });
  $("input[name='submit']").click(function() {
    handlers = 0;
  });
  $(window).bind("beforeunload", function() {
    if (handlers > 0) return false;
  });

  // perform ajax request to save string value
  $("a.save").click(function() {
    $trigger = $(this);
    row_name = $("textarea[name='rows["+ $(this).attr("data") +"][row_name]']").val();
    row_value = $("textarea[name='rows["+ $(this).attr("data") +"][row_value]']").val();
    
    $.ajax({
      type: "POST",
      url: "ajax.php",
      data: {{/literal} "action":"save_row", "project":"{$PROJECT}", "language":"{$LANGUAGE}", "file":"{$FILE}", "key":"{$SECRET_KEY}", "row_name": utf8_encode(row_name), "row_value": utf8_encode(row_value) {literal}}
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      if (msg.errcode == "success") {
        $trigger.parents("tr.main").removeClass("missing").addClass("new");
        overlayMessage(msg.data, "highlight", $trigger);
      }
      else {
        overlayMessage(msg.data, msg.errcode, $trigger);
      }
      
      handlers--;
    });
    
    return false;
  });
  {/literal}{/footer_script}
{/if}

{if $DISPLAY.mode=='search' and $SEARCH.where=='row_value'}
  {combine_script id="jquery.highlighttextarea" path="template/js/jquery.highlighttextarea.min.js" load="footer"}
  {combine_css path="template/js/jquery.highlighttextarea.css"}
  
  {footer_script}
  $("textarea:visible").highlightTextarea({ldelim}
    words: ["{$SEARCH.needle}","{'","'|implode:$SEARCH.words}"],
    caseSensitive: false,
    resizable: true
  });
  {/footer_script}
{/if}