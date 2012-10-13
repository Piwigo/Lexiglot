{include file="messages.tpl"}

{if $TYPE=='language'}
  <p class="caption">Talk about translations in <a href="{$lex->language_url($ITEM)}">{$lex->language_flag($ITEM)} {$lex->language_name($ITEM)}</a></p>
{else}
  <p class="caption">Talk about translation of <a href="{$lex->project_url($ITEM)}">{$lex->project_name($ITEM)}</a></p>
{/if}

{if not $EDITABLE}
  <div class="ckeditor">{$CONTENT}</div>
  
{else}
  <div id="editWarning" style="display:none;">
    {ui_message type='warning' icon='info' content="Please don't erase other users stuff and sign your modifications with your username."}
  </div>
  
  <form method="POST" action="{$SELF_URL}" id="talk_form">
    <input type="reset" value="Edit" class="red" id="editCK">
    
    <div id="buttons" style="display:none;">
      <input type="submit" name="save_talk" value="Save" class="blue">
      <input type="reset" id="cancelCK" value="Cancel" class="red">
      <br><br>
      <a href="#" id="insertSign">Insert username and date</a>
    </div>
    
    <div class="ckeditor">{$CONTENT}</div>
    
    <input type="hidden" name="key" value="{$SECRET_KEY}">
    <input type="hidden" name="type" value="{$TYPE}">
    <input type="hidden" name="item" value="{$ITEM}">
  </form>
  
  {combine_script id='functions' path='template/js/functions.js' load='footer}
  {combine_script id='ckeditor' path='template/js/ckeditor/ckeditor.js' load='footer}
  {combine_script id='ckeditor.jquery' path='template/js/ckeditor/adapters/jquery.js' load='footer}
  
  {footer_script}{literal}
  var config = {
    language: "en",
    toolbar : [
      { name: "document", items : ["Source", "-", "Print"] },
      { name: "clipboard", items : ["Cut", "Copy", "Paste", "PasteText", "PasteFromWord", "-", "Undo", "Redo"] },
      { name: "editing", items : ["Find", "Replace"] },
      { name: "tools", items : ["Maximize", "ShowBlocks", "-", "About"] },
      { name: "styles", items : ["Styles", "Format", "Font", "FontSize"] },
      "/",
      { name: "basicstyles", items : ["Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript", "-", "RemoveFormat"] },
      { name: "colors", items : ["TextColor", "BGColor"] },
      { name: "paragraph", items : ["NumberedList", "BulletedList", "-", "Outdent", "Indent", "-", "Blockquote", "CreateDiv", "-", "JustifyLeft", "JustifyCenter", "JustifyRight", "JustifyBlock"] },
      { name: "links", items : ["Link", "Unlink", "Anchor"] },
      { name: "insert", items : ["Image", "Table", "HorizontalRule", "Smiley", "SpecialChar", "Iframe"] }
    ],
    
    coreStyles_bold	: { element : "b" },
    coreStyles_italic : { element : "i" },
    
    extraPlugins : "tableresize,autogrow",
    removePlugins : "resize",
    autoGrow_onStartup : true,
    autoGrow_maxHeight : 600
  };
  
  $("#editCK").click(function() {
    $(".ckeditor").replaceWith(function() {
      return '<textarea class="ckeditor" name="content">'+ $(this).html() +'</textarea>';
    });

    $(".ckeditor").ckeditor(config);
    
    $("#editWarning").show();
    $("#buttons").show();
    $(this).hide();
    
    return false;
  });
  
  $("#cancelCK").click(function() {
    if (confirm('Are you sure?')) {
      $(".ckeditor").ckeditorGet().destroy(false);
      
      $(".ckeditor").replaceWith(function() {
        return '<div class="ckeditor">'+ decodeEntities($(this).html()) +'</div>';
      });
      
      $("#editWarning").hide();
      $("#editCK").show();
      $("#buttons").hide();
    }
    
    return false;
  });
  {/literal}
  
  $("#insertSign").click(function() {ldelim}
    var oEditor = $(".ckeditor").ckeditorGet();
    
    // Check the active editing mode.
    if ( oEditor.mode == "wysiwyg" ) {ldelim}
      oEditor.insertHtml('<br><i><a href="{$lex->user_url($USER.ID)}">{$USER.USERNAME}</a>, {$CURRENT_DATE}</i>');
    }
    else {ldelim}
      oEditor.insertText('{$USER.USERNAME}, {$CURRENT_DATE}');
    }
      
    return false;
  });
  {/footer_script}
{/if}