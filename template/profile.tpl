{include file="messages.tpl"}

<form action="{$SELF_URL}" method="post">

{* <!-- public profile --> *} 
  <fieldset class="common">
    <legend>Public profile</legend>
    <table class="login">
      <tr>
        <td>Username :</td>
        <td>{$user.username}</td>
      </tr>
      <tr>
        <td>Status :</td>
        <td><i>{$user.status}</i></td>
      </tr>
    {if $user.email_privacy=='public'}
      <tr>
        <td>E-mail :</td>
        <td><i><a href="mailto:{$user.email}">{$user.email}</a></i></td>
      </tr>
    {/if}
    {if $languages}
      <tr>
        <td>Languages assigned :</td>
        <td>
        {foreach from=$languages item=lang}
          <a href="{$lang.URL}" title="{$lang.NAME}" class="clean">{$lang.FLAG}</a>
        {/foreach}
        </td>
      </tr>
    {/if}
    {if $projects}
      <tr>
        <td>Projects assigned :</td>
        <td>
        {foreach from=$projects item=project name=foo}
          <a href="{$project.URL}" class="clean">{$project.NAME}</a>{if not $smarty.foreach.foo.last},{/if} 
        {/foreach}
        </td>
      </tr>
    {/if}
    {if $user.status=='manager' and $manage_projects}
      <tr>
        <td>Projects managed :</td>
        <td>
        {foreach from=$manage_projects item=project name=foo}
          <a href="{$project.URL}" class="clean">{$project.NAME}</a>{if not $smarty.foreach.foo.last},{/if} 
        {/foreach}
        </td>
      </tr>
    {/if}
    </table>
  </fieldset>

{* <!-- registration --> *}
{if $IS_PRIVATE and $CONF.allow_profile}
  <fieldset class="common">
    <legend>Registration</legend>
    <table class="login">
      <tr>
        <td><label for="email">Email address :</label></td>
        <td><input type="text" name="email" id="email" size="25" maxlength="64" value="{$user.email}"></td>
      </tr>
      <tr>
        <td><label for="password_new">New password :</label></td>
        <td><input type="password" name="password_new" id="password_new" size="25" maxlength="32"> <i>(leave blank to not change)</i></td>
      </tr>
      <tr>
        <td><label for="password_confirm">Confirm password :</label></td>
        <td><input type="password" name="password_confirm" id="password_confirm" size="25" maxlength="32"></td>
      </tr>
      <tr>
        <td><label for="password">Current password :</label></td>
        <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
      </tr>
    </table>
  </fieldset>
{/if}

{* <!-- preferences --> *}
{if $IS_PRIVATE}   
  <fieldset class="common">
    <legend>Preferences</legend>
    <table class="login">
      <tr>
        <td>Languages I speak :</td>
        <td>
          {if $IS_NEW}<div class="ui-state-warning ui-corner-all" style="padding:5px;"> <span class="ui-icon ui-icon-info" style="float:left; margin:3px 5px 0 0;"></span>{/if}
          
          <select id="my_languages" name="my_languages[]" multiple="multiple" data-placeholder="Select languages..." style="width:500px;">
            {html_options options=$all_languages selected=$user.my_languages}
            {html_style}.chzn-results li {ldelim} color:#111 !important; }{/html_style}
          </select>
          <br>
          <i>(please note this only for information, this doesn't change languages you have access to)</i>
          
          {if $IS_NEW}</div>{/if}
        </td>
      </tr>
      <tr>
        <td><label for="nb_rows">Number of rows per page :</label></td>
        <td><input type="text" name="nb_rows" id="nb_rows" size="3" maxlength="3" value="{$user.nb_rows}"></td>
      </tr>
      <tr>
        <td>Email visibility :</td>
        <td>
          <label><input type="radio" name="email_privacy" value="public" {if $user.email_privacy=='public'}checked="checked"{/if}> All registered users can view my email and send me messages</label><br>
          <label><input type="radio" name="email_privacy" value="hidden" {if $user.email_privacy=='hidden'}checked="checked"{/if}> Only admins can view my email and registered users can send me messages through Lexiglot</label><br>
          <label><input type="radio" name="email_privacy" value="private" {if $user.email_privacy=='private'}checked="checked"{/if}> Only admins can view my email and send me messages</label>
        </td>
      </tr>
      <tr>
        <td><input type="hidden" name="key" value="{$KEY}"></td>
        <td><input type="submit" name="save_profile" value="Submit" class="blue"></td>
      </tr>
    </table>
  </fieldset>
  
  {combine_script id="jquery.chosen" path="template/js/jquery.chosen.min.js" load="footer"}
  {combine_css path="template/js/jquery.chosen.css"}

  {footer_script}
    $("#my_languages").chosen();
  {/footer_script}
{/if}

{* <!-- stats --> *}
{if $stats}
  <fieldset class="common">
    <legend>Activity</legend>
    <div id="highstock" style="height: 350px;"></div>
  </fieldset>
  
  {combine_script id="jquery.highstock" path="template/js/jquery.highstock.min.js" load="footer"}
  
  {footer_script}{literal}
    $(function() {
      window.chart = new Highcharts.StockChart({
        chart : {
          renderTo : "highstock",
        },

        rangeSelector : {
          buttons: [
            {type: "month", count: 1, text: "1 month"}, 
            {type: "month", count: 3, text: "3 months"}, 
            {type: "month", count: 6, text: "6 months"}, 
            {type: "year", count: 1, text: "1 year"}, 
            {type: "year", count: 2, text: "2 years"}, 
            {type: "all", text: "All"}
          ],
          buttonTheme: { width: 80},
          selected : 0,
        },
        
        scrollbar: {
          barBackgroundColor: "#999",
          barBorderRadius: 7,
          barBorderWidth: 0,
          rifleColor: "#333",
          buttonBackgroundColor: "#999",
          buttonBorderWidth: 0,
          buttonBorderRadius: 7,
          buttonArrowColor: "#333",
          trackBackgroundColor: "none",
          trackBorderWidth: 1,
          trackBorderRadius: 8,
          trackBorderColor: "#CCC",
        },
        
        navigator: {
          handles: {
            backgroundColor: "#999",
            borderColor: "#555",
          },
        },
        
        series : [
    {/literal}
      {foreach from=$stats item=data key=lang}{strip}
          {ldelim}
            name : "{$lang}",
            data : [{$data}],
          },
      {/strip}{/foreach}
    {literal}
        ],
      });
    });
  {/literal}{/footer_script}
{/if}

{* <!-- contact --> *}
{if $contact}
  <fieldset class="common">
    <legend>Send e-mail</legend>
      <table class="login">
      <tr>
        <td>Subject :</td>
        <td><input type="text" name="subject" style="width:500px;" value="{$contact.MESSAGE}"></td>
      </tr>
      <tr>
        <td>Message :</td>
        <td><textarea name="message" style="width:500px;" rows="6" maxsize="70">{$contact.CONTENT}</textarea></td>
      </tr>
      <tr>
        <td></td>
        <td>Pease note that by using this form, your e-mail address will be disclosed to the recipient.</td>
      </tr>
      <tr>
        <td>
          <input type="hidden" name="key" value="{$KEY}">
          <input type="hidden" name="user_id" value="{$user.id}">
        </td>
        <td><input type="submit" name="send_message" value="Send" class="blue"></td>
      </tr>
    </table>
  </fieldset>
  
  {combine_script id="jquery.autoresize" path="template/js/jquery.autoresize.min.js" load="footer"}
  
  {footer_script}{literal}
    $("textarea").autoResize({
      maxHeight:2000,
      extraSpace:11
    });
  {/literal}{/footer_script}
{/if}

</form>


{combine_script id="jquery.tiptip" path="template/js/jquery.tiptip.min.js" load="footer"}
{combine_css path="template/js/jquery.tiptip.css"}

{footer_script}{literal}
  $(".flag").parent("a").css("cursor", "help").tipTip({ 
    maxWidth:"600px",
    delay:200,
    defaultPosition:"top"
  });
{/literal}{/footer_script}