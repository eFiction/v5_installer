<div class="grid-wrapper">
	<div class="gb-full"><h2>Optional modules:</h2></div>
	<?php foreach (($installerCFG['optional']?:array()) as $module=>$select): ?>
		<?php if ($select[0]!='*'): ?>
			<div class="gb-40">"<?php echo $module; ?>" (<?php echo $description[$module]; ?>)</div>
			<div class="gb-20"><?php if ($select[0]!='?'): ?>Current setting:<?php endif; ?>&nbsp;</div>
			<div class="gb-20">
				<?php if ($select[0]=='-'): ?><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=add.'.$module); ?>">Install module</a></div><div class="gb-20"><span class='error'>not installing.</span><?php endif; ?>
				<?php if ($select[0]=='+'): ?><span class='success'>installing</span></div><div class="gb-20"><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=drop.'.$module); ?>">Don't install</a><?php endif; ?>
				<?php if ($select[0]=='?'): ?><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=add.'.$module); ?>">Install</a></div><div class="gb-20"><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=drop.'.$module); ?>">Don't install</a><?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
	<div class="gb-full"><br /><h2>New core modules:</h2></div>
	<?php $ctr=0; foreach (($installerCFG['optional']?:array()) as $module=>$select): $ctr++; ?>
		<?php if ($select[0]=='*' AND $select[1]!='*'): ?><?php if ($opt=TRUE): ?><?php endif; ?>
			<div class="gb-40">"<?php echo $module; ?>" (<?php echo $description[$module]; ?>)</div>
			<div class="gb-20"><?php if ($select[0]!='?'): ?>Current setting:<?php endif; ?>&nbsp;</div>
			<div class="gb-20">
				<?php if ($select[1]=='-'): ?><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=add.'.$module); ?>">import data</a></div><div class="gb-20"><span class='error'>dropping old data</span><?php endif; ?>
				<?php if ($select[1]=='+'): ?><span class='success'>importing old data</span></div><div class="gb-20"><a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('stepsub', 'step=1,sub=drop.'.$module); ?>">drop old data</a><?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if (!@$opt): ?>
		<div class="gb-full">Nothing</div>
	<?php endif; ?>
	<div class="gb-full">
		<br />All installed modules can be deactivated at a later point.
	</div>
	<div class="gb-full">
		<br /><br />
		<a href="<?php echo $BASE; ?><?php echo \Base::instance()->alias('steps', 'step=2'); ?>">Continue</a>
	</div>
</div>