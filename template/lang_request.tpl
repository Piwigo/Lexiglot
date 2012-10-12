{include file="messages.tpl"}

<form action="{$SELF_URL}" method="post">
<fieldset class="common">
  <legend>Request a new language</legend>
  {ui_message type="highlight" icon="info" style="font-weight:bold;" content="Here is a full list of available languages. If you can't find your, feel free to send us a message, we will consider your request as soon as possible."}

  <ul id="languages" class="list-cloud">
  {foreach from=$all_languages item=lang}
    <li>{$lang.NAME} {$lang.FLAG}</li>
  {/foreach}
  </ul>
</fieldset>

<fieldset>
  <table class="login">
    <tr>
      <td>Language name :</td>
      <td><input type="text" name="language" value="{$request.LANGUAGE}"></td>
    </tr>
    <tr>
      <td>Message (optional) :</td>
      <td><textarea name="message" cols="50" rows="5">{$request.CONTENT}</textarea></td>
    </tr>
    <tr>
      <td><input type="hidden" name="key" value="{$KEY}"></td>
      <td><input type="submit" name="request_language" value="Send" class="blue"></td>
    </tr>
  </table>
</fieldset>
</form>