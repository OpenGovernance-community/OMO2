<?php
namespace dbObject;

class OrganizationExport
{
    public const FORMAT = 'openmyorganization-structure-export';
    public const VERSION = 4;

    public const MODULES = [
        'structure',
        'members',
        'documents',
        'projects',
        'tasks',
        'checklists',
        'indicators',
        'calendar',
        'pv',
    ];

    public static function build(Organization $organization, array $selectedModules): array
    {
        $organizationId = (int)$organization->getId();
        $rootHolon = $organization->getStructuralRootHolon();
        if ($organizationId <= 0 || !($rootHolon instanceof Holon)) {
            throw new \RuntimeException('Aucune structure exportable n a ete trouvee pour cette organisation.');
        }

        $selected = [];
        foreach (self::MODULES as $module) {
            $selected[$module] = !empty($selectedModules[$module]);
        }
        $selected['structure'] = true;
        if ($selected['tasks']) {
            $selected['projects'] = true;
        }
        if ($selected['pv']) {
            $selected['calendar'] = true;
        }

        $compact = $organization->getStructureCompactExportData($rootHolon);
        $holonCount = self::countTreeNodes((array)($compact['holons'] ?? []));
        $payload = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exportedAt' => date('c'),
            'source' => [
                'system' => 'omo2',
                'modules' => array_keys(array_filter($selected)),
            ],
            'scope' => [
                'organizationId' => $organizationId,
                'organizationName' => (string)$organization->get('name'),
                'organizationRootHolonId' => (int)$rootHolon->getId(),
                'navigationRootHolonId' => (int)$rootHolon->getId(),
                'exportRootHolonId' => (int)$rootHolon->getId(),
                'exportRootHolonName' => $rootHolon->getDisplayName(),
                'holonCount' => $holonCount,
            ],
            'organization' => [
                'sourceId' => $organizationId,
                'name' => (string)$organization->get('name'),
                'shortname' => (string)$organization->get('shortname'),
                'color' => (string)$organization->get('color'),
                'logo' => (string)$organization->get('logo'),
                'banner' => (string)$organization->get('banner'),
            ],
            'holons' => $compact['holons'] ?? [],
            'propertyDefinitions' => $compact['propertyDefinitions'] ?? [],
			'authorities' => $compact['authorities'] ?? [],
			'rules' => $compact['rules'] ?? [],
            'modules' => [],
        ];

        $builders = [
            'members' => 'buildMemberRecords',
            'documents' => 'buildDocumentRecords',
            'projects' => 'buildProjectRecords',
            'tasks' => 'buildTaskRecords',
            'checklists' => 'buildChecklistRecords',
            'indicators' => 'buildIndicatorRecords',
            'calendar' => 'buildEventRecords',
            'pv' => 'buildPvRecords',
        ];
        foreach ($builders as $module => $builder) {
            $documentExport = null;
            if ($selected[$module] && $module === 'documents') {
                $documentExport = self::buildDocumentExportData($organization);
                $records = $documentExport['records'];
            } else {
                $records = $selected[$module]
                    ? self::$builder($organization, $payload['holons'])
                    : [];
            }
            $payload['modules'][$module] = [
                'selected' => $selected[$module],
                'count' => count($records),
                'records' => $records,
            ];
            if (is_array($documentExport) && !empty($documentExport['omittedByType'])) {
                $payload['modules'][$module]['omittedCount'] = (int)$documentExport['omittedCount'];
                $payload['modules'][$module]['omittedByType'] = $documentExport['omittedByType'];
            }
        }

        $payload['modules']['structure'] = [
            'selected' => true,
            'count' => $holonCount,
            'records' => [],
        ];

        return $payload;
    }

    private static function countTreeNodes(array $nodes): int
    {
        $count = 0;
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $count++;
            $count += self::countTreeNodes((array)($node['children'] ?? []));
        }
        return $count;
    }

    private static function loadCollection(string $className, array $where, array $orderBy = []): array
    {
        $collection = new $className();
        $collection->load([
            'where' => $where,
            'orderBy' => $orderBy,
        ]);
        return iterator_to_array($collection, false);
    }

    private static function normalizeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            return null;
        }
        return $value;
    }

    private static function normalizeParameters($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function sourceUserId($object): int
    {
        return (int)($object->get('IDuser') ?: $object->get('IDusercreation'));
    }

    private static function flattenHolons(array $nodes, array &$map): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $id = (int)($node['id'] ?? 0);
            if ($id > 0) {
                $map[$id] = $node;
            }
            self::flattenHolons((array)($node['children'] ?? []), $map);
        }
    }

    private static function buildMemberRecords(Organization $organization, array $holons): array
    {
        $organizationId = (int)$organization->getId();
        $holonMap = [];
        self::flattenHolons($holons, $holonMap);
        $holonIds = array_keys($holonMap);
        $memberships = self::loadCollection('\\dbObject\\ArrayUserOrganization', [
            ['field' => 'IDorganization', 'value' => $organizationId],
        ], [
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $roleLinks = $holonIds === [] ? [] : self::loadCollection('\\dbObject\\ArrayUserHolon', [
            ['field' => 'IDholon', 'op' => 'in', 'value' => $holonIds],
            ['field' => 'is_membership', 'value' => 1],
        ], [
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $rolesByUser = [];
        foreach ($roleLinks as $roleLink) {
            $userId = (int)$roleLink->get('IDuser');
            $holonId = (int)$roleLink->get('IDholon');
            if ($userId <= 0 || $holonId <= 0 || !isset($holonMap[$holonId])) {
                continue;
            }
            $rolesByUser[$userId][] = [
                'sourceHolonId' => $holonId,
                'active' => (bool)$roleLink->get('active'),
                'createdAt' => self::normalizeValue($roleLink->get('datecreation')),
                'lastConnectionAt' => self::normalizeValue($roleLink->get('dateconnexion')),
            ];
        }

        $records = [];
        foreach ($memberships as $membership) {
            $userId = (int)$membership->get('IDuser');
            $user = new User();
            if ($userId <= 0 || !$user->load($userId)) {
                continue;
            }
            $records[] = [
                'sourceId' => $userId,
                'email' => (string)$membership->getScopedEmail(),
                'firstname' => (string)$user->get('firstname'),
                'lastname' => (string)$user->get('lastname'),
                'username' => (string)$membership->getScopedUsername(),
                'presentation' => (string)$membership->getScopedPresentation(),
                'active' => (bool)$membership->get('active'),
                'createdAt' => self::normalizeValue($membership->get('datecreation')),
                'lastConnectionAt' => self::normalizeValue($membership->get('dateconnexion')),
                'organizationMembership' => [
                    'isAdmin' => $membership->isOrganizationAdmin(),
                    'active' => (bool)$membership->get('active'),
                ],
                'roleAssignments' => array_values($rolesByUser[$userId] ?? []),
            ];
        }
        return $records;
    }

    private static function getExportableDocumentTypes(): array
    {
        return [
            Document::TYPE_HTML,
            Document::TYPE_EXTERNAL_LINK,
            Document::TYPE_FOLDER,
        ];
    }

    private static function buildDocumentExportData(Organization $organization): array
    {
        $documents = self::loadCollection('\\dbObject\\ArrayDocument', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
            ['field' => 'documenttype', 'op' => '!=', 'value' => Document::TYPE_PV],
        ], [
            ['field' => 'datecreation', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $records = [];
        $omittedByType = [];
        $exportableTypes = array_flip(self::getExportableDocumentTypes());
        foreach ($documents as $document) {
            $documentType = (string)$document->get('documenttype');
            if (!isset($exportableTypes[$documentType])) {
                $omittedByType[$documentType !== '' ? $documentType : 'unknown'] = (int)($omittedByType[$documentType !== '' ? $documentType : 'unknown'] ?? 0) + 1;
                continue;
            }
            $projectLinks = self::loadCollection('\\dbObject\\ArrayProjectDocument', [
                ['field' => 'IDdocument', 'value' => (int)$document->getId()],
            ]);
            $projectIds = [];
            foreach ($projectLinks as $projectLink) {
                $projectId = (int)$projectLink->get('IDproject');
                if ($projectId > 0) {
                    $projectIds[$projectId] = $projectId;
                }
            }
            $projectIds = array_values($projectIds);
            $records[] = [
                'sourceId' => (int)$document->getId(),
                'title' => (string)$document->get('title'),
                'description' => (string)$document->get('description'),
                'content' => (string)$document->get('content'),
                'externalUrl' => (string)$document->get('externalurl'),
                'documentType' => $documentType,
                'filename' => (string)$document->get('storedfilename'),
                'legacyFilePath' => (string)$document->get('storedfilepath'),
                'fileTransferRequired' => $document->get('documenttype') === Document::TYPE_UPLOADED_FILE,
                'sourceUserId' => self::sourceUserId($document),
                'sourceHolonId' => (int)$document->get('IDholon'),
                'sourceProjectId' => (int)($projectIds[0] ?? 0),
                'sourceProjectIds' => $projectIds,
                'sourceParentDocumentId' => (int)$document->get('IDdocument_parent'),
                'keywords' => (string)$document->get('keywords'),
                'isTemplate' => (bool)$document->get('is_template'),
                'openInNewWindow' => (bool)$document->get('openinnewwindow'),
                'projectVisibleInHolon' => (bool)$document->get('project_visible_in_holon'),
                'createdAt' => self::normalizeValue($document->get('datecreation')),
                'updatedAt' => self::normalizeValue($document->get('datemodification')),
                'active' => (bool)$document->get('active'),
            ];
        }
        ksort($omittedByType, SORT_STRING);
        return [
            'records' => $records,
            'omittedCount' => array_sum($omittedByType),
            'omittedByType' => $omittedByType,
        ];
    }

    private static function buildProjectRecord(Project $project): array
    {
        $team = new ArrayProjectUser();
        $team->loadForProject((int)$project->getId(), false);
        $teamMembers = [];
        foreach ($team as $member) {
            if (!($member instanceof ProjectUser)) {
                continue;
            }
            $userId = (int)$member->get('IDuser');
            if ($userId <= 0) {
                continue;
            }
            $teamMembers[] = [
                'sourceUserId' => $userId,
                'active' => (bool)$member->get('active'),
                'createdAt' => self::normalizeValue($member->get('datecreation')),
            ];
        }
        return [
            'sourceId' => (int)$project->getId(),
            'sourceUserId' => (int)$project->get('IDuser'),
            'sourceProposerUserId' => (int)$project->get('IDuser_proposed'),
            'sourceHolonId' => (int)$project->get('IDholon'),
            'sourceParentProjectId' => (int)$project->get('IDproject_parent'),
            'sourceJournalDocumentId' => (int)$project->get('IDdocument_journal'),
            'title' => (string)$project->get('title'),
            'description' => (string)$project->get('description'),
            'status' => (string)$project->get('status'),
            'proposalStatus' => (string)$project->get('proposal_status'),
            'proposedAt' => self::normalizeValue($project->get('proposed_at')),
            'proposalDecidedAt' => self::normalizeValue($project->get('proposal_decided_at')),
            'blockedReason' => (string)$project->get('blocked_reason'),
            'blockedUntil' => self::normalizeValue($project->get('blocked_until')),
            'blockedAutoReactivate' => (bool)$project->get('blocked_auto_reactivate'),
            'blockedReactivateStatus' => (string)$project->get('blocked_reactivate_status'),
            'plannedStartAt' => self::normalizeValue($project->get('planned_start_date')),
            'plannedEndAt' => self::normalizeValue($project->get('planned_end_date')),
            'priority' => $project->get('priority'),
            'importance' => $project->get('importance'),
            'calculatedImportance' => $project->get('calculated_importance'),
            'captureMode' => (string)$project->get('capture_mode'),
            'projectSize' => (string)$project->get('project_size'),
            'active' => (bool)$project->get('active'),
            'createdAt' => self::normalizeValue($project->get('created_at')),
            'updatedAt' => self::normalizeValue($project->get('updated_at')),
            'closedAt' => self::normalizeValue($project->get('closed_at')),
            'archivedAt' => self::normalizeValue($project->get('archived_at')),
            'teamMembers' => $teamMembers,
            'followerSourceUserIds' => ProjectFollower::getActiveUserIdsForProject((int)$project->getId()),
        ];
    }

    private static function buildProjectRecords(Organization $organization, array $unusedHolons): array
    {
        $projects = self::loadCollection('\\dbObject\\ArrayProject', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
            ['field' => 'project_kind', 'value' => Project::KIND_STANDARD],
            ['field' => 'IDproject_parent', 'op' => 'is null'],
        ], [
            ['field' => 'created_at', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        return array_map([self::class, 'buildProjectRecord'], $projects);
    }

    private static function buildTaskRecords(Organization $organization, array $unusedHolons): array
    {
        $tasks = self::loadCollection('\\dbObject\\ArrayProject', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
            ['field' => 'project_kind', 'value' => Project::KIND_STANDARD],
            ['field' => 'IDproject_parent', 'op' => 'is not null'],
        ], [
            ['field' => 'created_at', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        return array_map([self::class, 'buildProjectRecord'], $tasks);
    }

    private static function buildChecklistRecords(Organization $organization, array $unusedHolons): array
    {
        $checklists = self::loadCollection('\\dbObject\\ArrayChecklist', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
        ], [
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $records = [];
        foreach ($checklists as $checklist) {
            $rootTemplate = $checklist->getTemplateRoot();
            if (!($rootTemplate instanceof Project)) {
                continue;
            }
            $trigger = null;
            foreach ($checklist->getTriggers(false) as $candidate) {
                if ($candidate instanceof ChecklistTrigger) {
                    $trigger = $candidate;
                    break;
                }
            }
            $triggerType = $trigger instanceof ChecklistTrigger
                ? ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type'))
                : ChecklistTrigger::TYPE_CONTAINER;
            $runs = new ArrayChecklistRun();
            $runs->loadForChecklist((int)$checklist->getId());
            $runRecords = [];
            foreach ($runs as $run) {
                if (!($run instanceof ChecklistRun)) {
                    continue;
                }
                $runItems = [];
                foreach ($run->getItems() as $runItem) {
                    if (!($runItem instanceof ChecklistRunItem)) {
                        continue;
                    }
                    $runItems[] = [
                        'sourceChecklistItemId' => (int)$runItem->get('IDchecklistitem'),
                        'sourceProjectId' => (int)$runItem->get('IDproject'),
                        'state' => (string)$runItem->get('state'),
                        'activationAt' => self::normalizeValue($runItem->get('activation_at')),
                        'createdAt' => self::normalizeValue($runItem->get('created_at')),
                        'updatedAt' => self::normalizeValue($runItem->get('updated_at')),
                        'activatedAt' => self::normalizeValue($runItem->get('activated_at')),
                        'completedAt' => self::normalizeValue($runItem->get('completed_at')),
                    ];
                }
                $runRecords[] = [
                    'sourceId' => (int)$run->getId(),
                    'sourceTriggerId' => (int)$run->get('IDchecklisttrigger'),
                    'sourceHolonId' => (int)$run->get('IDholon'),
                    'sourceRootProjectId' => (int)$run->get('IDproject_root'),
                    'sourceCreatorUserId' => (int)$run->get('IDuser_created'),
                    'status' => (string)$run->get('status'),
                    'scheduledFor' => self::normalizeValue($run->get('scheduled_for')),
                    'createdAt' => self::normalizeValue($run->get('created_at')),
                    'updatedAt' => self::normalizeValue($run->get('updated_at')),
                    'completedAt' => self::normalizeValue($run->get('completed_at')),
                    'items' => $runItems,
                ];
            }
            $items = [];
            foreach ($checklist->getItems(false) as $item) {
                if (!($item instanceof ChecklistItem)) {
                    continue;
                }
                $itemTemplate = $item->getProjectTemplate();
                if (!($itemTemplate instanceof Project)) {
                    continue;
                }
                $recurrence = $item->getRecurrence();
                $itemRecord = [
                    'sourceId' => (int)$item->getId(),
					'sourceProjectId' => (int)$itemTemplate->getId(),
                    'sourceUserId' => (int)$itemTemplate->get('IDuser'),
					'sourceHolonId' => (int)$itemTemplate->get('IDholon'),
					'sourceParentProjectId' => (int)$itemTemplate->get('IDproject_parent'),
                    'stableKey' => (string)$item->get('stable_key'),
                    'title' => (string)$itemTemplate->get('title'),
                    'description' => (string)$itemTemplate->get('description'),
                    'active' => (bool)$item->get('active'),
                    'position' => (int)$item->get('position'),
                    'activation' => [
                        'type' => (string)$item->get('activation_type'),
                        'delayValue' => (int)$item->get('delay_value'),
                        'delayUnit' => (string)$item->get('delay_unit'),
                        'displayLeadValue' => (int)$item->get('display_lead_value'),
                        'displayLeadUnit' => (string)$item->get('display_lead_unit'),
                        'executionDurationValue' => (int)$item->get('execution_duration_value'),
                        'executionDurationUnit' => (string)$item->get('execution_duration_unit'),
                    ],
                ];
				$dependencies = [];
				foreach ($item->getDependencies() as $dependency) {
					if (!($dependency instanceof ChecklistItemDependency)) {
						continue;
					}
					$dependencies[] = [
						'sourceRequiredItemId' => (int)$dependency->get('IDchecklistitem_required'),
						'delayValue' => (int)$dependency->get('delay_value'),
						'delayUnit' => (string)$dependency->get('delay_unit'),
					];
				}
				if ($dependencies !== []) {
					$itemRecord['dependencies'] = $dependencies;
				}
                if ($recurrence instanceof ChecklistItemRecurrence) {
                    $itemRecord['recurrence'] = [
                        'frequency' => (string)$recurrence->get('frequency'),
                        'schedule' => (string)$recurrence->get('schedule'),
                        'displayLeadValue' => (int)$recurrence->get('display_lead_value'),
                        'displayLeadUnit' => (string)$recurrence->get('display_lead_unit'),
                        'executionDurationValue' => (int)$recurrence->get('execution_duration_value'),
                        'executionDurationUnit' => (string)$recurrence->get('execution_duration_unit'),
                    ];
                }
                $items[] = $itemRecord;
            }
            $records[] = [
				'recordType' => 'process',
                'sourceId' => (int)$checklist->getId(),
                'sourceUserId' => (int)$rootTemplate->get('IDuser'),
				'sourceRootProjectId' => (int)$rootTemplate->getId(),
				'sourceResponsibleUserId' => (int)$checklist->get('IDuser_responsible'),
                'sourceHolonId' => (int)$rootTemplate->get('IDholon'),
                'title' => (string)$rootTemplate->get('title'),
                'description' => (string)$rootTemplate->get('description'),
                'status' => (string)$checklist->get('status'),
                'revisionNote' => (string)$checklist->get('revision_note'),
                'active' => (bool)$checklist->get('active'),
                'kind' => $triggerType === ChecklistTrigger::TYPE_MANUAL ? 'standalone' : 'container',
                'trigger' => [
                    'sourceId' => $trigger instanceof ChecklistTrigger ? (int)$trigger->getId() : 0,
                    'type' => $triggerType,
                    'stableKey' => $trigger instanceof ChecklistTrigger ? (string)$trigger->get('stable_key') : 'primary',
					'frequency' => $trigger instanceof ChecklistTrigger ? (string)$trigger->get('frequency') : '',
					'schedule' => $trigger instanceof ChecklistTrigger ? (string)$trigger->get('schedule') : '',
                    'overlapPolicy' => $trigger instanceof ChecklistTrigger ? (string)$trigger->get('overlap_policy') : ChecklistTrigger::OVERLAP_BLOCK,
                    'enabled' => $trigger instanceof ChecklistTrigger && (bool)$trigger->get('enabled'),
                ],
                'items' => $items,
                'runs' => $runRecords,
            ];
        }

		$activities = self::loadCollection('\\dbObject\\ArrayControlActivity', [
			['field' => 'IDorganization', 'value' => (int)$organization->getId()],
		], [
			['field' => 'position', 'dir' => 'ASC'],
			['field' => 'id', 'dir' => 'ASC'],
		]);
        foreach ($activities as $activity) {
            $checks = new ArrayControlTaskCheck();
            $checks->loadForTask((int)$activity->getId());
            $checkRecords = [];
            foreach ($checks as $check) {
                if (!($check instanceof ControlTaskCheck)) {
                    continue;
                }
                $checkRecords[] = [
                    'sourceUserId' => (int)$check->get('IDuser'),
                    'scheduledFor' => self::normalizeValue($check->get('scheduled_for')),
                    'checkedAt' => self::normalizeValue($check->get('checked_at')),
                    'createdAt' => self::normalizeValue($check->get('created_at')),
                ];
            }
            $records[] = [
				'recordType' => 'recurring_activity',
				'sourceId' => (int)$activity->getId(),
				'sourceHolonId' => (int)$activity->get('IDholon'),
				'sourceResponsibleUserId' => (int)$activity->get('IDuser_responsible'),
				'active' => (bool)$activity->get('active'),
				'position' => (int)$activity->get('position'),
				'createdAt' => self::normalizeValue($activity->get('created_at')),
				'updatedAt' => self::normalizeValue($activity->get('updated_at')),
				'items' => [[
					'sourceId' => (int)$activity->getId(),
					'title' => (string)$activity->get('title'),
					'description' => (string)$activity->get('description'),
					'active' => (bool)$activity->get('active'),
					'recurrence' => [
						'frequency' => (string)$activity->get('frequency'),
						'schedule' => (string)$activity->get('schedule'),
						'displayLeadValue' => (int)$activity->get('display_lead_value'),
						'displayLeadUnit' => (string)$activity->get('display_lead_unit'),
						'executionDurationValue' => (int)$activity->get('execution_duration_value'),
						'executionDurationUnit' => (string)$activity->get('execution_duration_unit'),
					],
					'checks' => $checkRecords,
				]],
			];
		}
        return $records;
    }

    private static function buildIndicatorRecords(Organization $organization, array $unusedHolons): array
    {
        $indicators = self::loadCollection('\\dbObject\\ArrayStatIndicator', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
        ], [
            ['field' => 'name', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $records = [];
        foreach ($indicators as $indicator) {
            $values = [];
            foreach ($indicator->getMeasurements() as $value) {
                if (!($value instanceof StatIndicatorValue)) {
                    continue;
                }
                $values[] = [
                    'value' => $value->get('value'),
                    'measuredAt' => self::normalizeValue($value->get('measured_at')),
                    'sourceUserId' => (int)$value->get('IDuser'),
                ];
            }
            $records[] = [
                'sourceId' => (int)$indicator->getId(),
                'sourceUserId' => (int)$indicator->get('IDuser'),
                'sourceHolonId' => (int)$indicator->get('IDholon'),
                'name' => (string)$indicator->get('name'),
                'description' => (string)$indicator->get('description'),
                'sourceUrl' => (string)$indicator->get('source_url'),
                'referenceType' => (string)$indicator->get('reference_type'),
                'referenceScale' => (string)$indicator->get('reference_scale'),
                'measurementFrequency' => (string)$indicator->get('measurement_frequency'),
                'measurementSchedule' => (string)$indicator->get('measurement_schedule'),
                'chartMinValue' => $indicator->get('chart_min_value'),
                'showCumulative' => (bool)$indicator->get('show_cumulative'),
                'active' => (bool)$indicator->get('active'),
                'createdAt' => self::normalizeValue($indicator->get('created_at')),
                'values' => $values,
            ];
        }
        return $records;
    }

    private static function buildEventRecords(Organization $organization, array $unusedHolons): array
    {
        $events = self::loadCollection('\\dbObject\\ArrayEvent', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
        ], [
            ['field' => 'start_at', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $records = [];
        foreach ($events as $event) {
            $records[] = [
                'sourceId' => (int)$event->getId(),
                'sourceUserId' => (int)$event->get('IDuser'),
                'sourceHolonId' => (int)$event->get('IDholon'),
                'title' => (string)$event->get('title'),
                'description' => (string)$event->get('description'),
                'status' => (string)$event->get('status'),
                'timezone' => (string)$event->get('timezone'),
                'locationMode' => (string)$event->get('locationmode'),
                'locationAddress' => (string)$event->get('locationaddress'),
                'videoMeetingUrl' => (string)$event->get('videomeetingurl'),
                'startAt' => self::normalizeValue($event->get('start_at')),
                'endAt' => self::normalizeValue($event->get('end_at')),
                'isAllDay' => (bool)$event->get('is_all_day'),
                'active' => (bool)$event->get('active'),
                'createdAt' => self::normalizeValue($event->get('created_at')),
                'updatedAt' => self::normalizeValue($event->get('updated_at')),
            ];
        }
        return $records;
    }

    private static function buildPvRecords(Organization $organization, array $unusedHolons): array
    {
        $documents = self::loadCollection('\\dbObject\\ArrayDocument', [
            ['field' => 'IDorganization', 'value' => (int)$organization->getId()],
            ['field' => 'documenttype', 'value' => Document::TYPE_PV],
        ], [
            ['field' => 'datecreation', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ]);
        $records = [];
        foreach ($documents as $document) {
            $event = $document->getAssociatedEvent();
            if (!($event instanceof Event)) {
                continue;
            }
            $history = [];
            $points = new ArrayDocumentPvPoint();
            $points->loadForDocument((int)$document->getId(), false);
            foreach ($points as $point) {
                $history[] = [
                    'sourceId' => (int)$point->getId(),
                    'sourceUserId' => (int)$point->get('IDuser_author'),
                    'sourceModificationUserId' => (int)$point->get('IDuser_modification'),
                    'sourceHolonId' => (int)$point->get('IDholon_concerned'),
                    'sourceParentId' => (int)$point->get('IDparent'),
                    'itemType' => (string)$point->get('item_type'),
                    'title' => (string)$point->get('title'),
                    'description' => (string)$point->get('content'),
                    'authorEmail' => (string)$point->get('author_email'),
                    'pointtype' => (string)$point->get('pointtype'),
                    'position' => (int)$point->get('position'),
                    'priority' => (int)$point->get('priority'),
                    'desiredDurationMinutes' => (int)$point->get('desired_duration_minutes'),
                    'actualDurationMinutes' => (int)$point->get('actual_duration_minutes'),
                    'isHandled' => (bool)$point->get('is_handled'),
                    'isConfidential' => (bool)$point->get('is_confidential'),
                    'createdAt' => self::normalizeValue($point->get('datecreation')),
                    'updatedAt' => self::normalizeValue($point->get('datemodification')),
                    'active' => (bool)$point->get('active'),
                ];
            }
            $records[] = [
                'sourceId' => (int)$document->getId(),
                'sourceMeetingId' => (int)$event->getId(),
                'sourceHolonId' => (int)$document->get('IDholon'),
                'sourceSecretaryUserId' => (int)$document->get('IDuser_pv_editor'),
                'meetingTitle' => (string)$event->get('title'),
                'meetingScratchpad' => (string)$event->get('description'),
                'scheduledAt' => self::normalizeValue($event->get('start_at')),
                'openedAt' => self::normalizeValue($document->get('datecreation')),
                'closedAt' => self::normalizeValue($document->get('datemodification')),
                'history' => $history,
            ];
        }
        return $records;
    }
}
