<?php
if (!defined('MAINWP_WORDFENCE_PATH')) { exit; }
/**
 * Presents multiple boolean options under a single heading with a checkbox toggle control for each.
 *
 * Expects $options and $title to be defined. $options is an array of 
 * 	array(
 * 		'name' => <option name>, 
 * 		'enabledValue' => <value saved if the toggle is enabled>, 
 * 		'disabledValue' => <value saved if the toggle is disabled>,
 * 		'value' => <current value of the option>,
 * 		'title' => <title displayed to label the checkbox>
 * 	)
 * 
 * $helpLink may also be defined.
 *
 * @var array $options The options shown. The structure is defined above.
 * @var string $title The overall title shown for the options.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the options will be tagged as premium only and not allow its values to change for free users.
 */
?>
<ul class="wf-option wf-option-toggled-multiple">
	<li class="wf-option-title"><?php echo esc_html($title); ?></li>
	<li class="wf-option-checkboxes">
		<?php
		foreach ($options as $o):
		?>
		<ul data-option="<?php echo esc_attr($o['name']); ?>" data-enabled-value="<?php echo esc_attr($o['enabledValue']); ?>" data-disabled-value="<?php echo esc_attr($o['disabledValue']); ?>" data-original-value="<?php echo esc_attr($o['value'] == $o['enabledValue'] ? $o['enabledValue'] : $o['disabledValue']); ?>">
			<li class="wf-option-checkbox<?php echo ($o['value'] == $o['enabledValue'] ? ' wf-checked' : ''); ?>"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></li>
			<li class="wf-option-title"><?php echo esc_html($o['title']); ?></li>
		</ul>
		<?php endforeach; ?>
	</li>
</ul>