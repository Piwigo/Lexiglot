{$TABSHEET_ADMIN}

{include file="messages.tpl"}


{* <!-- search mails --> *}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>From</th>
      <th>To</th>
      <th>Subject</th>
      <th>Limit</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" size="20" name="from_mail" value="{$SEARCH.from_mail}"></td>
      <td><input type="text" size="20" name="to_mail" value="{$SEARCH.to_mail}"></td>
      <td><input type="text" size="20" name="subject" value="{$SEARCH.subject}"></td>
      <td><input type="text" size="3" name="limit" value="{$SEARCH.limit}"></td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Reset">
      </td>
    </tr>
  </table>
</fieldset>
</form>

{* <!-- mails list --> *}
<form id="mail_history">
<fieldset class="common">
  <legend>History</legend>
  
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="from">From</th>
        <th class="to">To</th>
        <th class="date">Date</th>
        <th class="subject">Subject</th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$mails item=row}
      <tr class="{$row.HIGHLIGHT}">
        <td class="from">{$row.FRO}</td>
        <td class="to">{$row.TO}</td>
        <td class="date">
          <span style="display:none;">{$row.TIME}</span>
          {$row.DATE}
        </td>
        <td class="subject">{$row.SUBJECT}</td>
      </tr>
    {foreachelse}
      <tr>
        <td colspan="4"><i>No results</i></td>
      </tr>
    {/foreach}
    </tbody>
  </table>
  
  <div class="pagination">{$PAGINATION}</div>
</fieldset>
</form>

<table class="legend">
  <tr>
    <td><span>&nbsp;</span>User mails</td>
    <td><span class="highlight">&nbsp;</span> System mails</td>
  </tr>
</table>


{combine_script id="jquery.tablesorter" path="template/js/jquery.tablesorter.min.js" load="footer"}
{combine_css path="template/js/jquery.tablesorter.css"}

{footer_script}{literal}
$("#mail_history table").tablesorter({
  sortList: [[2,1]],
  widgets: ["zebra"]
});
{/literal}{/footer_script}