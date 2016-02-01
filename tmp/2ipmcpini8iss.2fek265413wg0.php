		<div class="grid-wrapper">
			<?php if (isset($currently)): ?><div class="gb-full"><h2>Currently processing `<?php echo $currently; ?>`</h2></div><?php endif; ?>
			<?php if (isset($continue['step'])): ?>
				<div class="gb-full">
					<b><?php echo $continue['message']; ?></b>, click <a href='<?php echo $BASE; ?>
					<?php if (isset( $continue['sub'] )): ?>
						<?php echo \Base::instance()->alias("stepsub", "step={$continue['step']},sub={$continue['sub']}"); ?>'
						<?php else: ?><?php echo \Base::instance()->alias("steps", "step={$continue['step']}"); ?>'
					<?php endif; ?>
						>->here<-</a> to continue<?php if (isset( $continue['message2'] )): ?><?php echo $continue['message2']; ?><?php endif; ?>.<br />
				</div>
			<?php endif; ?>
			<?php if (isset($error)): ?>
				<div class="gb-50 alert-box error">
					<?php echo nl2br($error); ?><br />
				</div>
			<?php endif; ?>
			<?php if (isset($link)): ?>
				<div class="gb-50">
					<a href='<?php echo $BASE; ?><?php echo \Base::instance()->alias("stepsub", "step={$link['step']},sub={$link['sub']}"); ?>'>->Click here<-</a> to <?php echo $link['message']; ?>
				</div>
			<?php endif; ?>
			<?php $ctr=0; foreach ((@$reports?:array()) as $report): $ctr++; ?>
				<div class="gb-30"><small>#<?php echo $ctr; ?>: <?php echo $report['step']; ?></small></div>
				<div class="gb-60"><span class="alert-box <?php echo $report['class']; ?>"><small><?php echo $report['message']; ?></small></div><?php endforeach; ?>
		</div>
		<?php if (isset($redirect)): ?>
			<script type='text/javascript'>
				function delayedRedirect(){
					window.location = '<?php echo $BASE; ?><?php echo \Base::instance()->alias("steps", "step={$redirect}"); ?>'
				}
			</script>
<?php endif; ?>