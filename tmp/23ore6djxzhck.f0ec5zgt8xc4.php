<h2>Select chapter storage location</h2>
<div>
During upgrade, you can select to move your chapter data to a new location.<br />
Below are a few brief recommendations for your hosting environment, a detailed overview can be found in the wiki:
</div>
<div>
<?php if ($installerCFG['test'][0] == 2 AND $installerCFG['test'][1] > 0 AND $installerCFG['test'][2] > 0): ?>

	Connected to database server, see advice below:</br ></br >
	<?php if ($scenario == 5): ?>
		<div class="grid-wrapper">
			<div class='alert-box warning gb-50'>Connecting to a remote database server.</div>
			<div class='alert-box success gb-50'>Local SQLite supported and recommended.</div>
		</div>
		<div>
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Use local (filebased) storage</a> (Recommended)<br />
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">Use remote database storage</a>
		</div>
	<?php endif; ?>
	<?php if ($scenario == 4): ?>
		<div class="grid-wrapper">
			<div class='alert-box warning gb-50'>Connecting to a remote database server.</div>
			<div class='alert-box error gb-50'>Local SQLite is not available, must use remote server.</div>
		</div>
		<div>
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">Use remote database storage</a> (Required)<br />
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Refer to the wiki on possible ways to activate local storage.</a>
		</div>
	<?php endif; ?>
	<?php if ($scenario < 4): ?>
		<div class="grid-wrapper">
			<div class='alert-box success gb-50'>Connecting to a local database server.</div>
		</div>
	<?php endif; ?>
	<?php if ($scenario == 3): ?>
		<div class="grid-wrapper">
			<div class='alert-box warning gb-50'>Limited UTF8 support through MySQL and no SQLite support (only affects languages with 4byte characters and emoji).</div>
		</div>
		<div>
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Use local database storage</a> (Required)<br />
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">If 4-byte UTF is required, refer to the wiki for possible ways to activate local storage.</a>
		</div>
	<?php endif; ?>
	<?php if ($scenario == 2): ?>
		<div class="grid-wrapper">
			<div class='alert-box success gb-50'>SQLite is not supported, but the database has full character support.</div>
		</div>
		<div>
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Use local database storage</a> (Required)<br />
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">If desired, refer to the wiki for possible ways to activate local storage.</a>
		</div>
	<?php endif; ?>
	<?php if ($scenario == 1): ?>
		<div class="grid-wrapper">
			<div class='alert-box success gb-50'>Limited UTF8 support through MySQL, but SQLite is available and has full character support.</div>
		</div>
		<div>
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Use local (filebased) storage</a> (Recommended if 4-byte UTF is required)<br />
			<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">Use local database storage</a>
		</div>
	<?php endif; ?>
	<?php if ($scenario == 0): ?>
	<div class="grid-wrapper">
		<div class='alert-box success gb-50'>Local database and SQLite available, both with full character support.</div>
	</div>
	<div>
		<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=database'); ?>">Use database storage</a><br />
		<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('storage', 'where=filebase'); ?>">Use local (filebased) storage</a>
	</div>
	<?php endif; ?>

<?php else: ?>
	<div class='alert-box error'>Could not connect to database server, please <a href="upgrade/config">see the configuration page</a></div>

<?php endif; ?>
</div>