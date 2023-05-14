<?php

use Sprint\Migration\Builders;

return [
	"version_builders" => \Sprint\Migration\VersionConfig::getDefaultBuilders() + [
		"OrderPropsExport"        => Builders\OrderPropsExport::class
	]
];
