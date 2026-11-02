<?php

use dbObject\PropertyFormat;

$dashboardHtmlContent = PropertyFormat::sanitizeHtml($dashboardModuleSettings['html'] ?? '');
