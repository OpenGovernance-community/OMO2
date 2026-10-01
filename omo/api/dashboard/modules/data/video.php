<?php

use dbObject\VideoEmbedHelper;

$dashboardVideoEmbedData = VideoEmbedHelper::getEmbedData($dashboardModuleSettings['video'] ?? '');
