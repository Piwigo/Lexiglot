{include file="messages.tpl"}


{if $IN_LOGIN}
<form action="{$SELF_URL}" method="post">
  <p class="legend">Login</p>
  
  <table class="login">
    <tr>
      <td><label for="username">Username :</label></td>
      <td><input type="text" name="username" id="username" size="25" maxlength="32" value="{$user.USERNAME}"></td>
    </tr>
    <tr>
      <td><label for="password">Password :</label></td>
      <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
    </tr>
    <tr>
      <td><label for="remember_me">Remember me :</label></td>
      <td><input type="checkbox" name="remember_me" id="remember_me" value="1" {if $user.REMEMBER}checked="checked"{/if}></td>
    </tr>
    <tr>
      <td>
        <input type="hidden" name="key" value="{$KEY}">
        <input type="hidden" value="{$REFERER}" name="referer">
      </td>
      <td>
        <input type="submit" name="login" value="Login" class="blue">
        <a href="user.php?password" rel="nofollow">Lost your password?</a>
      </td>
    </tr>
  </table>
</form>
  
{elseif $IN_PASSWORD}
<form action="{$SELF_URL}" method="post">
  <p class="legend">Password reset</p>

  <table class="login">
    <tr>
      <td><label for="username">Username :</label></td>
      <td><input type="text" name="username" id="username" size="25" maxlength="32" value="{$user.USERNAME}"></td>
    </tr>
    <tr>
      <td><label for="email">Email address :</label></td>
      <td><input type="text" name="email" id="email" size="25" maxlength="64" value="{$user.EMAIL}"></td>
    </tr>
    <tr>
      <td><input type="hidden" name="key" value="{$KEY}"></td>
      <td>
        <input type="submit" name="reset_password" value="Submit" class="blue">
        <span class="red">All fields are required</span>
      </td>
    </tr>
  </table>
</form>
  
{elseif $IN_REGISTER}
<form action="{$SELF_URL}" method="post">
  <p class="legend">Register</p>
  
  <table class="login">
    <tr>
      <td><label for="username">Username :</label></td>
      <td><input type="text" name="username" id="username" size="25" maxlength="32" value="{$user.USERNAME}"></td>
    </tr>
    <tr>
      <td><label for="password">Password :</label></td>
      <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
    </tr>
    <tr>
      <td><label for="email">Email address :</label></td>
      <td><input type="text" name="email" id="email" size="25" maxlength="64" value="{$user.EMAIL}"></td>
    </tr>
    <tr>
      <td><input type="hidden" name="key" value="{$KEY}"></td>
      <td>
        <input type="submit" name="register" value="Submit" class="blue">
        <span class="red">All fields are required</span>
      </td>
    </tr>
  </table>
</form>
{/if}


{footer_script}
$("input[type='text']:first", document.forms[0]).focus();
{/footer_script}