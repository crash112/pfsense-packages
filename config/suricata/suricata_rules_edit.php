<?php
/*
 * suricata_rules_edit.php
 *
 * Copyright (C) 2014 Bill Meeks
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * 
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

require_once("guiconfig.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

$flowbit_rules_file = FLOWBITS_FILENAME;
$suricatadir = SURICATADIR;

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}
$a_rule = &$config['installedpackages']['suricata']['rule'];

$id = $_GET['id'];
if (is_null($id)) {
	header("Location: /suricata/suricata_interfaces.php");
	exit;
}

if (isset($id) && $a_rule[$id]) {
	$pconfig['enable'] = $a_rule[$id]['enable'];
	$pconfig['interface'] = $a_rule[$id]['interface'];
	$pconfig['rulesets'] = $a_rule[$id]['rulesets'];
}

/* convert fake interfaces to real */
$if_real = suricata_get_real_interface($pconfig['interface']);
$suricata_uuid = $a_rule[$id]['uuid'];
$suricatacfgdir = "{$suricatadir}suricata_{$suricata_uuid}_{$if_real}";
$file = $_GET['openruleset'];
$contents = '';
$wrap_flag = "off";

// Correct displayed file title if necessary
if ($file == "Auto-Flowbit Rules")
	$displayfile = FLOWBITS_FILENAME;
else
	$displayfile = $file;

// Read the contents of the argument passed to us.
// It may be an IPS policy string, an individual SID,
// a standard rules file, or a complete file name.
// Test for the special case of an IPS Policy file.
if (substr($file, 0, 10) == "IPS Policy") {
	$rules_map = suricata_load_vrt_policy($a_rule[$id]['ips_policy']);
	if (isset($_GET['ids'])) {
		$contents = $rules_map[$_GET['gid']][trim($_GET['ids'])]['rule'];
		$wrap_flag = "soft";
	}
	else {
		$contents = "# Suricata IPS Policy - " . ucfirst($a_rule[$id]['ips_policy']) . "\n\n";
		foreach (array_keys($rules_map) as $k1) {
			foreach (array_keys($rules_map[$k1]) as $k2) {
				$contents .= "# Category: " . $rules_map[$k1][$k2]['category'] . "   SID: {$k2}\n";
				$contents .= $rules_map[$k1][$k2]['rule'] . "\n";
			}
		}
	}
	unset($rules_map);
}
// Is it a SID to load the rule text from?
elseif (isset($_GET['ids'])) {
	// If flowbit rule, point to interface-specific file
	if ($file == "Auto-Flowbit Rules")
		$rules_map = suricata_load_rules_map("{$suricatacfgdir}rules/" . FLOWBITS_FILENAME);
	else
		$rules_map = suricata_load_rules_map("{$suricatadir}rules/{$file}");
	$contents = $rules_map[$_GET['gid']][trim($_GET['ids'])]['rule'];
	$wrap_flag = "soft";
}

// Is it our special flowbit rules file?
elseif ($file == "Auto-Flowbit Rules")
	$contents = file_get_contents("{$suricatacfgdir}rules/{$flowbit_rules_file}");
// Is it a rules file in the ../rules/ directory?
elseif (file_exists("{$suricatadir}rules/{$file}"))
	$contents = file_get_contents("{$suricatadir}rules/{$file}");
// Is it a fully qualified path and file?
elseif (file_exists($file))
	if (substr(realpath($file), 0, strlen(SURICATALOGDIR)) != SURICATALOGDIR)
		$contents = gettext("\n\nERROR -- File: {$file} can not be viewed!");
	else
		$contents = file_get_contents($file);
// It is not something we can display, so exit.
else
	$input_errors[] = gettext("Unable to open file: {$displayfile}");

$pgtitle = array(gettext("Suricata"), gettext("File Viewer"));
?>

<?php include("head.inc");?>

<body link="#000000" vlink="#000000" alink="#000000">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php // include("fbegin.inc");?>

<form action="suricata_rules_edit.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td class="tabcont">
		<table width="100%" cellpadding="0" cellspacing="6" bgcolor="#eeeeee">
		<tr>
			<td class="pgtitle" colspan="2">Suricata: Rules Viewer</td>
		</tr>
		<tr>
			<td width="20%">
				<input type="button" class="formbtn" value="Return" onclick="window.close()">
			</td>
			<td align="right">
				<b><?php echo gettext("Rules File: ") . '</b>&nbsp;' . $displayfile; ?>&nbsp;&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
		<tr>
			<td valign="top" class="label" colspan="2">
			<div style="background: #eeeeee; width:100%; height:100%;" id="textareaitem"><!-- NOTE: The opening *and* the closing textarea tag must be on the same line. -->
			<textarea style="width:100%; height:100%;" wrap="<?=$wrap_flag?>" rows="33" cols="80" name="code2"><?=$contents;?></textarea>
			</div>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
</form>
<?php // include("fend.inc");?>
</body>
</html>
