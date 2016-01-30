<?php if (extension_loaded('pdo')): ?>
<form method="post" action="">
	<fieldset>
		<legend>Database server settings (See <a href="http://efiction.org/wiki/Installer_Config">the Wiki entry</a> for help.)</legend>
		<div class="grid-wrapper">
			<div class="gb-20">DB driver:</div>
			<div class="gb-80">
				<select name="new[dbdriver]">
					<?php if (extension_loaded('pdo_mysql')): ?><option value="mysql">MySQL</option><?php endif; ?>
					<?php if (extension_loaded('pdo_pgsql')): ?><option value="pgsql">PostgreSQL *</option><?php endif; ?>
					<?php if (extension_loaded('pdo_mssql')): ?><option value="mssql">MS SQL/Sybase *</option><?php endif; ?>
				</select> <small>(Drivers marked with * are experimental, at best)</small>
			</div>
			<div class="gb-full"><small>Available drivers are auto-detected.</small></div>
			<div class="gb-20">Server:</div><div class="gb-80"><input type="text" name="new[dbhost]" value="<?php echo @$installerCFG['dbhost']; ?>"> <small>(localhost when using socket, otherwise use hostname or IP)</small></div>
			<div class="gb-20">Socket/port:</div><div class="gb-80"><input type="text" name="new[dbport]" value="<?php echo @$installerCFG['dbport']; ?>"> <small>(Empty for default)</small></div>
			<div class="gb-20">Username:</div><div class="gb-80"><input type="text" name="new[dbuser]" value="<?php echo @$installerCFG['dbuser']; ?>">&nbsp;</div>
			<div class="gb-20">Password:</div><div class="gb-80"><input type="password" name="new[dbpass]" value="<?php echo @$installerCFG['dbpass']; ?>"> </div>
			<div class="gb-full"><small>Below is just some technical information, mainly for troubleshooting:</small></div>
			<div class="gb-20">Character set:</div><div class="gb-10"><small>(auto detect)&nbsp;</small></div><div class="gb-70"><?php echo @$installerCFG['charset']; ?>&nbsp;</div>
			<div class="gb-20">DSN string:</div><div class="gb-10"><small>(auto built)&nbsp;</small></div><div class="gb-70"><?php echo @$installerCFG['dsn'][3]; ?>&nbsp;</div>
		</div>
	</fieldset>
	<div class="grid-wrapper">
		<?php if (@$installerCFG['test'][0] == 2): ?><div class="gb-50 alert-box success">Server OK</div><?php endif; ?>
		<?php if (@$installerCFG['test'][0] == 1): ?><div class="gb-50 alert-box warning">Cannot probe server without an eFiction 3.5.x database name</div><?php endif; ?>
		<?php if (@$installerCFG['test'][0] == 0 AND @$installerCFG['dsn']!=''): ?><div class="gb-50 alert-box error">Failed to connect to database Server.<br />Last error was: <?php echo $installerCFG['error']; ?></div><?php endif; ?>
	</div>
	<br />
	<fieldset>
		<legend>eFiction 3.5.x specific</legend>
		<div class="grid-wrapper">
			<div class="gb-20">Database:</div><div class="gb-80"><input type="text" name="new[dbname]" value="<?php echo @$installerCFG['dbname']; ?>"> &nbsp;</div>
			<div class="gb-20">Settings prefix:</div><div class="gb-80"><input type="text" name="new[settings]" value="<?php echo @$installerCFG['settings']; ?>"> <small>(if your settings table is "fanfiction_settings", then this field should be empty)</small></div>
			<div class="gb-20">Sitekey:</div><div class="gb-80"><input type="text" name="new[sitekey]" value="<?php echo @$installerCFG['sitekey']; ?>"><?php if (isset($installerCFG['data']['sitename'] )): ?><small> Found sitename: <b><?php echo $installerCFG['data']['sitename']; ?></b></small><?php endif; ?></div>
		</div>
	</fieldset>
	<div class="grid-wrapper">
		<?php if (isset($installerCFG['test']) AND $installerCFG['test'][0] == 2): ?>
			<?php if ($installerCFG['test'][1] == 2): ?><div class="gb-50 alert-box success">eFiction 3.5.x settings validated</div><?php endif; ?>
			<?php if ($installerCFG['test'][1] == 1): ?><div class="gb-50 alert-box warning">eFiction 3.5.x database found, please check sitekey</div><?php endif; ?>
			<?php if ($installerCFG['test'][1] == 0): ?><div class="gb-50 alert-box error">Failed to open eFiction 3.5.x database.<br />Last error was: <?php echo $installerCFG['error']; ?></div><?php endif; ?>
		<?php endif; ?>
	</div>
	<br />
	<fieldset>
		<legend>eFiction 5.x specific</legend>
		<div class="grid-wrapper">
			<div class="gb-20">Database:</div><div class="gb-80"><input type="text" name="new[db_new]" value="<?php echo @$installerCFG['db_new']; ?>"> &nbsp;</div>
			<div class="gb-20">Prefix:</div><div class="gb-80"><input type="text" name="new[pre_new]" value="<?php echo @$installerCFG['pre_new']; ?>"> &nbsp;</div>
		</div>
	</fieldset>
	<div class="grid-wrapper">
		<?php if (isset($installerCFG['test']) AND $installerCFG['test'][0] == 2): ?>
			<?php if ($installerCFG['test'][2] == 2 AND $installerCFG['db_new']!=''): ?><div class="gb-50 alert-box success">eFiction 5.x database available</div><?php endif; ?>
			<?php if ($installerCFG['test'][2] == 1): ?><div class="gb-50 alert-box warning">eFiction 5.x database available, but tables with this prefix already exist.</div><?php endif; ?>
			<?php if ($installerCFG['test'][2] == 0): ?><div class="gb-50 alert-box error">Failed to open eFiction 5.x database.<br />Last error was: <?php echo $installerCFG['error']; ?></div><?php endif; ?>
		<?php endif; ?>
	</div>
	<br />
	<input type="submit"><input type="reset">
</form>

<?php else: ?>No PDO driver in place, cannot continue.
<?php endif; ?>
