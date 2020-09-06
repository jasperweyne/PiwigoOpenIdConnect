<div class="pageTitle">
	<h2>OpenID Connect</h2>
</div>

<form method="post" action="" class="properties">
<fieldset id="mainConf">
  <ul>
    <li>
      <label>
        <input type="checkbox" name="enable" {if $enable}checked="checked"{/if}>
        <b>{'Enable OpenID Connect'|translate}</b>
      </label>
    </li>
  </ul>
</fieldset>

<p style="text-align:left;"><input type="submit" name="save_config" value="{'Save Settings'|translate}"></p>
</form>

<div style="text-align:right;">
  Developed by Jasper Weyne
</div>