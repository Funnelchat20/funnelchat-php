<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Generated;

/**
 * The schemas of the spec as PHPStan type aliases (array shapes): the decoded JSON of the API.
 * The resource methods use them in their PHPDoc. To use one in your code, import it:
 * `@phpstan-import-type ContactResource from \Funnelchat\Generated\Schemas`.
 *
 * Schemas that start with `Pending` come from spec/pending.json.
 *
 * @phpstan-type AccountsUserResource array{
 *     id: string,
 *     name: string,
 *     email: string,
 *     admin: string|0,
 *     role: string,
 *     token: string|'',
 *     first_login_at: string,
 *     last_login_at: string,
 *     first_login_form: bool|null,
 *     without_business_type: bool,
 *     image: string,
 *     phone: string,
 *     timezone: string,
 *     locale: string|'es',
 *     owner_id: string,
 *     owner: array{
 *         id: string,
 *         name: string,
 *         email: string,
 *     }|null,
 *     plan: list<mixed>|'',
 *     effective_business_numbers_limit?: int|null,
 *     effective_agents_limit?: int|null,
 *     advisor_scope: string,
 *     show_advisor_name: string,
 *     contacts: string|int,
 *     groups: string|int,
 *     devices: string,
 *     additional_devices: string,
 *     agents: string,
 *     payment_till: string,
 *     last_payment_at: string|'',
 *     feature_versions?: string,
 *     panel_usage?: array{
 *         percentage: string,
 *         ceiling: string,
 *         used_count: string,
 *         reachable_count: string,
 *         total_count: string,
 *         tier: string,
 *         is_agent: string,
 *         blocks: string,
 *         computed_at: string|null,
 *     }|null,
 *     created_at: string|'',
 *     updated_at: string|'',
 *     is_dormant: bool,
 *     dormant_expires_at: string|null,
 *     dormant_provider: 'hotmart'|'stripe',
 *     can_enter_dormant?: bool|null,
 *     mac_enforcement_enabled: bool,
 *     available_mac_buckets_sum: int,
 *     password_set?: bool,
 *     password_set_at?: string|null,
 * }
 * @phpstan-type ActionGroupResource array{
 *     id: string,
 *     name: string,
 *     status: string,
 *     info: mixed,
 *     action_name: string,
 *     action_label: string|null,
 *     executed_at: string|null,
 *     executed_by: string|null,
 *     groups: string|list<string>,
 * }|array{
 *     id: string,
 *     name: string|null,
 *     status: string,
 *     info: mixed,
 *     action_name: string|null,
 *     action_label: string|null,
 *     executed_at: string|null,
 *     executed_by: string|null,
 *     groups: list<mixed>|list<string>,
 * }
 * @phpstan-type ActionResource array{
 *     id: string,
 *     action: string,
 *     old_value: string,
 *     new_value: string,
 *     field: string,
 *     status: ActionStatusEnum,
 *     tries: string,
 *     process_at: string|'',
 *     created_at: string|'',
 *     completed_at: string|null,
 *     stats: array{
 *         total_groups: string,
 *         pending: string,
 *         running: string,
 *         completed: string,
 *         failed: string,
 *         skipped: string,
 *     },
 *     groups: string,
 *     subActions: list<array<string, mixed>>,
 * }
 * @phpstan-type ActionStatusEnum 0|1|2|3|4|5|6|7|8|9|10|11|12
 * @phpstan-type AlertAction 'disconnected'|'usage_80'|'expiring'|'usage_100'|'upcoming_charge'|'payment_failed'|'plan_blocked'|'reactivated'|'trial_ending'|'cancellation_confirmed'|'dormant_ending'|'blocked'|'no_groups_available'|'daily_report'|'action_failed'|'message_failed'|'full'|'invalid_link'|'low_balance'|'topup_succeeded'|'topup_failed'
 * @phpstan-type AlertCampaignResource array{
 *     id: string,
 *     name: string,
 *     is_active: bool,
 *     groups_count: int,
 *     type: int|null,
 * }
 * @phpstan-type AlertCatalogResource array{
 *     module: string,
 *     action: string,
 *     category: string,
 *     is_critical: string,
 *     label: string,
 *     tooltip: string,
 * }
 * @phpstan-type AlertDailyReportResource array{
 *     enabled: bool,
 *     send_time: string|null,
 *     all_campaigns: bool,
 *     campaign_ids: string|list<string>,
 * }
 * @phpstan-type AlertDestinationResource array{
 *     id: int,
 *     type: string,
 *     destination: string,
 *     name: string|null,
 *     verified: bool,
 *     has_secret: string,
 *     created_at: string|null,
 *     settings?: list<AlertSettingResource>,
 * }
 * @phpstan-type AlertDestinationType 'phone'|'webhook'
 * @phpstan-type AlertModule 'device'|'mac'|'billing'|'subscription'|'account'|'campaign'|'group'|'ai_quota'
 * @phpstan-type AlertQuietHourResource array{
 *     enabled: bool,
 *     start_time: string|null,
 *     end_time: string|null,
 * }
 * @phpstan-type AlertSettingResource array{
 *     module: string,
 *     action: string,
 *     enabled: bool,
 * }
 * @phpstan-type AppModulesScheduledMessagesResourcesMessageTemplateResource array{
 *     id: string,
 *     type: string,
 *     value: string,
 *     mentioned: bool,
 *     metrics: string,
 *     reactions: string,
 *     votes: string,
 *     event_responses: string,
 *     shipping_order: string,
 *     variations: list<MessageTemplateVariationResource>,
 * }
 * @phpstan-type AppModulesScheduledMessagesResourcesScheduledMessageResource array{
 *     id: string,
 *     name: string|'',
 *     send_at: string|null,
 *     status: string,
 *     is_draft: string|bool,
 *     type: string,
 *     folder: array{
 *         id: string,
 *         name: string,
 *     }|null,
 *     audience: string|0,
 *     total_readed: mixed,
 *     total_played: mixed,
 *     link_clicks: int,
 *     whalink_custom_domain: string,
 *     shipping_speed: string,
 *     campaign_ids: string,
 *     group_ids: string|null,
 *     excluded_group_ids: string|list<string>,
 *     static_audience: string,
 *     campaigns: string,
 *     groups: string,
 *     total_groups: string,
 *     messages: list<AppModulesScheduledMessagesResourcesMessageTemplateResource>,
 *     total_left_after_send: string,
 *     can_be_edited: string,
 *     recurrence: array{
 *         enabled: bool,
 *         frequency: string,
 *         interval: string,
 *         week_days: string,
 *         end_type: string,
 *         end_date: string,
 *         occurrences: string,
 *         current_occurrence: string,
 *         only_new_groups: string,
 *         only_full_groups: string,
 *     }|array{
 *         enabled: bool,
 *     },
 * }
 * @phpstan-type AvailableGroupResource array{
 *     uid: string|'',
 *     name: string|'',
 *     description: string|'',
 *     status: string|'available',
 *     campaign_id: string|null,
 * }
 * @phpstan-type BroadcastContactResource array{
 *     id: string,
 *     name: string,
 *     phone: string,
 *     whatsapp_id: string,
 *     email: string,
 *     image: string,
 * }
 * @phpstan-type BroadcastCostResource array{
 *     broadcast_id: string,
 *     currency: 'USD',
 *     is_partial: bool,
 *     estimate: array{
 *         category: string|null,
 *         currency: string,
 *         contacts: int,
 *         unpriced_contacts: int,
 *         total: float|int,
 *         per_market: list<array{
 *             market: string,
 *             contacts: int,
 *             rate: float|int|null,
 *             cost: float|int,
 *         }>,
 *         rates_updated_at: string|null,
 *         computed_at: string|null,
 *     }|null,
 *     computed: array{
 *         total: float|int,
 *         sent: int,
 *         billed: int,
 *         free: int,
 *         pending_price: int,
 *         no_data: int,
 *         category: string|int|array<string, mixed>|null,
 *         per_market: list<array{
 *             market: string,
 *             contacts: int,
 *             rate: float|int|null,
 *             cost: float|int,
 *         }>,
 *     },
 *     rates_updated_at: string|null,
 * }
 * @phpstan-type BroadcastCostTiersResource array{
 *     tiers: string|list<string>,
 *     rates_updated_at: string|null,
 * }
 * @phpstan-type BroadcastFolderResource array{
 *     id: int,
 *     name: string,
 *     broadcasts_count: string|0,
 * }
 * @phpstan-type BroadcastMessageResource array{
 *     contact_id: string,
 *     contact_name: string|'',
 *     contact_phone: string|'',
 *     contact_image: string|'',
 *     message_status: string|null,
 *     message_info: array{
 *         message: mixed,
 *         error_code: mixed,
 *         error: mixed,
 *         error_category: mixed,
 *     },
 *     skip_reason: string|null,
 *     skipped_at: string|null,
 *     recipient_state: string,
 *     sent_at: string|null,
 * }
 * @phpstan-type BroadcastResource array{
 *     id: int,
 *     name: string,
 *     device_id: int|null,
 *     template_id: int|'',
 *     template: string|null,
 *     folder: array{
 *         id: int,
 *         name: string,
 *     }|null,
 *     value: list<mixed>|null,
 *     template_button_flows: list<mixed>|null,
 *     included_tags: list<TagResource>,
 *     excluded_tags: list<TagResource>,
 *     some_tags: list<TagResource>,
 *     countries: list<mixed>|null,
 *     kanban_setting_id: int|null,
 *     kanban_stage_ids: list<mixed>|null,
 *     only_stagnant: bool,
 *     lead_score_categories: list<mixed>|null,
 *     lead_score_min: int|null,
 *     lead_score_max: int|null,
 *     date_from: string,
 *     date_to: string,
 *     send_at: string,
 *     contacts_count: string|int|null,
 *     delivered_messages_count: null,
 *     readed_messages_count: null,
 *     created_at: string,
 *     updated_at: string,
 *     status: BroadcastStatusEnum,
 *     shipping_speed: BroadcastShippingSpeedEnum,
 *     risk_acceptance: array{
 *         accepted_by_name: string|null,
 *         accepted_at: string,
 *         audience_count: int|null,
 *         provider: string|null,
 *         risk_codes: string|list<string>,
 *     }|null,
 *     missing_data_audit: array{
 *         audience_count: int,
 *         affected_count: int,
 *         items: string|list<string>,
 *         unknown_custom_fields: string|list<string>,
 *     }|null,
 *     smart_send: bool,
 *     smart_send_state: array{
 *         batch_size: string,
 *         pages_per_batch: string,
 *         current_batch: string,
 *         total_batches: string,
 *         quality: string,
 *         quality_baseline: string,
 *         checked_at: string,
 *         resume_page: string,
 *         page_size: string,
 *         total_pages: string,
 *         paused_at: string,
 *         pause_reason: string,
 *         quality_read_failures: string,
 *         cancelled_at: string,
 *     }|null,
 *     failure_reason: array{
 *         reason: string|null,
 *     }|null,
 *     send_mode: BroadcastSendModeEnum,
 *     protect_template_category: bool,
 * }
 * @phpstan-type BroadcastSendModeEnum 'now'|'schedule'|'after_approval'
 * @phpstan-type BroadcastShippingSpeedEnum 0|1|2
 * @phpstan-type BroadcastStatusEnum 0|1|2|3|4|5|6|7|8|9
 * @phpstan-type BroadcastsResource array{
 *     id: string,
 *     name: string,
 *     device_id: string,
 *     template_id: string|'',
 *     folder_id: string,
 *     value: string,
 *     date_from: string|'',
 *     date_to: string|'',
 *     send_at: string|'',
 *     contacts_count: string,
 *     created_at: string,
 *     updated_at: string|'',
 *     status: string,
 *     shipping_speed: string,
 *     delivered_messages_count: string|null,
 *     readed_messages_count: string|null,
 *     replied_messages_count: string|null,
 *     failure_reason: array{
 *         reason: string|null,
 *     }|null,
 *     send_mode: string,
 *     protect_template_category: bool,
 * }
 * @phpstan-type BulkMoveStageRequest array{
 *     contact_ids: list<int>,
 *     stage_id: int,
 * }
 * @phpstan-type BulkRemoveStageRequest array{
 *     contact_ids: list<int>,
 * }
 * @phpstan-type CampaignConditionalResource array{
 *     id: string,
 *     type: string,
 *     conditions: string,
 *     action_type: string,
 *     redirect_url: string,
 *     redirect_group_id: string,
 *     redirect_group_name?: string|null,
 *     is_group_exclusive: string,
 *     is_active: string,
 *     order: string,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type CampaignParticipantResource array{
 *     id: string,
 *     phone: string,
 *     name: string,
 *     lid: string,
 *     group_id: string,
 *     group_name?: string|null,
 *     joined_at: string|null,
 *     left_at: string|null,
 *     status: string,
 *     source: string,
 * }
 * @phpstan-type CampaignResource array{
 *     id: string,
 *     uuid: string,
 *     name: string,
 *     slug: string,
 *     photo: string|'',
 *     description: string|'',
 *     pixel: string,
 *     admins: string,
 *     group_admins: string,
 *     disconnected_groups: string,
 *     connection_error_groups: string,
 *     deviceId: string,
 *     userId: string,
 *     type: string,
 *     groups: string,
 *     is_active: string,
 *     redirect_url: string,
 *     traffic_distribution_type: string|null,
 *     fill_groups_enabled: string,
 *     group_numbering_position: string|null,
 *     anti_bot_verification_enabled: string,
 *     custom_domain: string,
 *     captcha_enabled: string,
 *     virtual_queue_enabled: bool,
 *     virtual_queue_rate_per_minute: int,
 *     group_name_variations: string,
 *     moderation?: array{
 *         type: string,
 *         insults_and_profanity: string,
 *         negative_comments: string,
 *         repetitive_spam: string,
 *         external_links: string,
 *         promotions_and_sales: string,
 *         off_topic: string,
 *     },
 *     members_can_send_messages: string,
 *     clicks_limit: string,
 *     participants_limit: string,
 *     backup_groups_count: string|0,
 *     admins_per_group: string,
 *     input_tag: string,
 *     output_tag: string,
 *     send_always_same_group: string,
 *     total_participants: string,
 *     total_clicks: string,
 *     total_in: string,
 *     total_out: string,
 *     total_in_counter: int,
 *     total_out_counter: int,
 *     auto_create_groups: bool,
 *     group_creation_suspended: string|bool,
 *     suspended_creator_phones: string|list<string>,
 *     group_creation_has_available_creator: string|bool,
 *     api_policy?: array<string, mixed>,
 * }
 * @phpstan-type CampaignWebhookLogResource array{
 *     id: string,
 *     campaign_id: string,
 *     webhook_id: string,
 *     webhook_type: string,
 *     url: string,
 *     payload: string,
 *     response_status: string,
 *     response_body: string,
 *     execution_time_ms: string,
 *     success: string,
 *     error_message: string,
 *     created_at: string,
 * }
 * @phpstan-type CampaignWebhookResource array{
 *     id: string,
 *     type: string,
 *     url: string,
 *     is_active: string,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type CampaignsResource array{
 *     id: string,
 *     uuid: string,
 *     name: string,
 *     custom_domain: string,
 *     photo: string|'',
 *     deviceId: string,
 *     userId: string,
 *     type: string,
 *     groups: string,
 *     total_clicks: string,
 *     total_in: string,
 *     total_in_counter: int,
 *     admins: string,
 *     group_admins: string,
 *     disconnected_groups: string,
 *     connection_error_groups: string,
 *     running_actions_count: string,
 *     pending_actions_count: string,
 *     pending_sync_groups: string,
 *     group_creation_suspended: bool,
 *     suspended_creator_phones: list<string>,
 *     group_creation_has_available_creator: bool,
 *     api_open_groups?: int,
 *     api_policy?: array{
 *         officialNumber: string|null,
 *         officialLid: string|null,
 *         companionNumbers: string,
 *     },
 * }
 * @phpstan-type ChannelMetadataResource array{
 *     name: string|null,
 *     description: string|null,
 *     image: string|null,
 *     link: string|null,
 *     admins_numbers: list<string>,
 *     participants_numbers: list<string>,
 *     verified_admin: bool,
 *     subscribers_count: string|0,
 *     participants: list<string>,
 * }
 * @phpstan-type ChannelMinResource array{
 *     id: string|null,
 *     uid: string|null,
 *     name: string|'',
 *     description: string|'',
 *     state: string|null,
 *     subscribers_count: int,
 *     invite_link: string|null,
 *     verification: string|null,
 *     picture: string|null,
 *     preview: string|null,
 *     creation_time: int|null,
 *     mute: string|null,
 *     role: string|null,
 *     group_id: string|null,
 * }
 * @phpstan-type CommunitiesMessageResource array{
 *     id: string,
 *     uid: string,
 *     type: string,
 *     value: mixed,
 *     scheduledMessage: ScheduledMessageResource,
 * }
 * @phpstan-type ContactCustomFieldResource array{
 *     id: int,
 *     name: string,
 *     description: string,
 *     type: string,
 *     value: string,
 *     created_at: string,
 *     updated_at: string,
 *     contact_count: string,
 * }
 * @phpstan-type ContactFlowListResource array{
 *     id: string,
 *     name: string,
 *     running: bool,
 *     created_at: string|null,
 *     last_run_at: string|null,
 * }
 * @phpstan-type ContactKanbanStageBoardResource array{
 *     kanban_setting_id: string,
 *     kanban_name: string,
 *     current_stage_id: string,
 *     value_custom_field_id: string,
 *     profit_custom_field_id: string,
 *     stages: string,
 * }
 * @phpstan-type ContactLeadScoreResource array{
 *     score: string,
 *     signals_count: string,
 *     category: string,
 *     confidence: string,
 *     disqualified: string,
 *     disqualified_at: string,
 *     disqualification_reasons: string,
 *     updated_at: string|null,
 *     events: list<LeadScoreEventResource>,
 *     next_cursor: string,
 *     has_more: bool,
 * }
 * @phpstan-type ContactNoteResource array{
 *     id: int,
 *     contact_id: int,
 *     author_id: int|null,
 *     author_name: mixed,
 *     body: string,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type ContactResource array{
 *     id: int,
 *     name: string,
 *     email: string,
 *     phone: string,
 *     whatsapp_id: string,
 *     whatsapp_username?: string|null,
 *     has_phone: bool,
 *     country_code: string,
 *     status: int,
 *     image: string,
 *     notes: string|null,
 *     custom_fields?: list<ContactCustomFieldResource>,
 *     created_at: string|null,
 *     active_conversation: int,
 *     favorite: int,
 *     unread_messages: int,
 *     manually_unread?: bool,
 *     last_message?: array{
 *         text: string,
 *         file: string,
 *         date: string,
 *         type: string,
 *         own: string,
 *         deleted_at: string,
 *         deleted_scope: string,
 *     },
 *     device_id: int|null,
 *     adviser_id: int|null,
 *     paused_at: string|null,
 *     mac_date: string|null,
 *     ai_assistant_name: string|null,
 *     stage_since?: string,
 *     is_stagnant?: bool,
 *     lead_score?: array{
 *         score: int,
 *         signals_count: int,
 *         category: string,
 *         confidence: string,
 *         disqualified: bool,
 *         disqualified_at: string|null,
 *         updated_at: string|null,
 *     },
 * }
 * @phpstan-type ContactTagResource array{
 *     id: int,
 *     name: string,
 *     description: string|'',
 *     color: string,
 *     created_at: string|null,
 *     updated_at: string|null,
 *     contact_count: string,
 *     assigned_at: string|null,
 *     last_updated: string|null,
 * }
 * @phpstan-type ConversationsMessageResource array{
 *     chat_id: string,
 *     contact_id: int|'',
 *     content: list<mixed>|'',
 *     device_id: int|'',
 *     error_code: string,
 *     file: string,
 *     forwarded: int,
 *     group_id: int|'',
 *     id: int,
 *     origin: MessageOriginEnum|'',
 *     origin_id: int|'',
 *     own: int,
 *     reference_message: string|null,
 *     reference_message_uid: string|null,
 *     is_quotable: bool,
 *     status: MessageStatusEnum|null,
 *     temporal_uuid: string|'',
 *     text: string,
 *     time: string|null,
 *     type: MessageTypeEnum|null,
 *     uid: string,
 *     info: list<mixed>|'',
 *     user_id: int,
 *     deleted_at: string|null,
 *     deleted_scope: string|null,
 *     poll_results?: string,
 *     event_rsvps?: string,
 *     form_answers?: string,
 *     reactions: list<mixed>,
 *     file_original_name: string|null,
 *     client_reference: string|null,
 * }
 * @phpstan-type ConversationsUserResource array{
 *     id: string,
 *     name: string,
 *     email: string,
 *     owner_id: string,
 *     advisor_scope: string,
 *     created_at: string,
 *     updated_at: string|'',
 * }
 * @phpstan-type CustomDomainResource array{
 *     id: int,
 *     hostname: string,
 *     status: string,
 *     ssl: array{
 *         validation_method: string,
 *         validation_string: string|null,
 *     },
 *     dns_records: list<array{
 *         type: 'ALIAS',
 *         host: '@',
 *         value: string,
 *         purpose: string,
 *     }|array{
 *         type: 'CNAME',
 *         host: string,
 *         value: string,
 *         purpose: string,
 *     }|array{
 *         type: 'TXT',
 *         host: string,
 *         value: string|null,
 *         purpose: string,
 *     }>,
 *     detected_provider: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type CustomFieldResource array{
 *     id: int,
 *     name: string,
 *     type: CustomFieldTypeEnum,
 *     description: string|null,
 *     contact_count?: int,
 *     created_at: string|null,
 * }
 * @phpstan-type CustomFieldTypeEnum 0|1|2|3|4
 * @phpstan-type DeviceConnectionEventEnum 0|1
 * @phpstan-type DeviceConnectionEventResource array{
 *     id: int,
 *     phone: string|null,
 *     event: DeviceConnectionEventEnum,
 *     created_at: string,
 * }
 * @phpstan-type DeviceFleetLogResource array{
 *     id: int,
 *     method: string,
 *     url: string|null,
 *     status_code: int|null,
 *     is_error: bool,
 *     duration_ms: int|null,
 *     created_at: string|null,
 *     request_payload: list<mixed>|null,
 *     response_body: list<mixed>|null,
 *     device_id: string|null,
 *     device_label: string|null,
 * }
 * @phpstan-type DeviceHealthDetailResource array{
 *     health: string,
 *     identity: string,
 *     entities: string,
 *     throughput_level: string,
 *     history: list<array{
 *         field: string,
 *         previous_value: string,
 *         new_value: string,
 *         detail: string|null,
 *         source: string,
 *         occurred_at: string,
 *     }>,
 * }
 * @phpstan-type DeviceLogResource array{
 *     id: int,
 *     method: string,
 *     url: string|null,
 *     status_code: int|null,
 *     is_error: bool,
 *     duration_ms: int|null,
 *     created_at: string|null,
 *     request_payload: list<mixed>|null,
 *     response_body: list<mixed>|null,
 * }
 * @phpstan-type DeviceResource array{
 *     id: int,
 *     alias: string,
 *     image: string,
 *     color: string,
 *     'number': string,
 *     normalized_number: string,
 *     first_conecction_at: string,
 *     user_id: int,
 *     provider: WhatsAppProviderEnum,
 *     waba_id: string,
 *     whatsapp_username: string|null,
 *     is_business: int,
 *     connected: int,
 *     active: int|null,
 *     health: string,
 *     number_key: string|null,
 *     number_key_confirmed: bool,
 *     is_coexistence: bool,
 *     slot_state: 'free'|'reserved'|'pending'|'held',
 *     pool_suspended: bool,
 *     pool_suspension_reason: 'over_pool'|'payment_lapse'|null,
 *     pool_keep: bool,
 *     qr_allowed: bool,
 *     can_change_to_meta: bool,
 *     capabilities: PendingDeviceCapabilities,
 *     connection?: PendingConnectionType,
 *     external_reference?: PendingExternalReference,
 * }
 * @phpstan-type DisconnectSurveyResource array{
 *     pending: bool,
 *     phone: string|null,
 * }
 * @phpstan-type ExtensionTokenResource array{
 *     token: string,
 *     expiresAt: int,
 *     expiresInSeconds: int,
 * }
 * @phpstan-type FlowResource array{
 *     id: string,
 *     name: string|'',
 *     uuid: string,
 *     first_node_uuid: string,
 *     enable: string,
 *     valid: string,
 *     executions: string|0,
 *     created_at: string,
 *     updated_at: string|'',
 *     nodes_count: string|0,
 *     trigger_uuid?: string|'',
 *     trigger_device_ids: string|list<string>,
 *     trigger_type: string|'',
 *     trigger_values?: string|list<string>,
 *     trigger_is_global: bool,
 *     trigger_save_activation_phrase: bool,
 *     trigger_activation_phrase_field_id: string|null,
 *     payment_platform: string|null,
 *     payment_external_product_id: string|null,
 *     payment_event_types: string|null,
 *     payment_event_entry_nodes: string|null,
 *     default: string,
 *     once_per_contact: string,
 *     frequency_limit_value: string,
 *     frequency_limit_unit: string|null,
 *     frequency_limit_minutes: string,
 *     folder?: FolderResource,
 *     nodes?: list<NodeResource>,
 *     intention_prompt: string|'',
 *     match_location: string|null,
 *     match_details: string|null,
 *     risk_acceptance: array{
 *         accepted_by_name: string|null,
 *         accepted_at: string,
 *         codes: string|list<string>,
 *     }|null,
 * }
 * @phpstan-type FolderResource array{
 *     id: string,
 *     name: string|'',
 *     parent_id: string,
 *     children?: list<array<string, mixed>>,
 *     flows_count?: int,
 * }
 * @phpstan-type ForwardMessageRequest array{
 *     targets: list<array{
 *         contact_id: int,
 *         device_id: int,
 *     }>,
 *     client_reference?: PendingClientReference,
 * }
 * @phpstan-type ForwardResultResource array{
 *     contact_id: string,
 *     mode: string,
 *     status: string,
 *     reason: string,
 * }
 * @phpstan-type GroupMetadataResource array{
 *     name: string|'',
 *     description: string|null,
 *     image: string|null,
 *     link: string|null,
 *     admins_numbers: list<string|null>,
 *     participants_numbers: list<string|null>,
 *     verified_admin: bool,
 *     subscribers_count: int,
 *     participants: list<mixed>,
 *     owner: string|null,
 * }
 * @phpstan-type GroupMinResource array{
 *     uid: string|'',
 *     name: string|'',
 *     description: string|'',
 *     device_id: string|'',
 *     alias: string|'',
 *     'number': string|'',
 *     communityUid: string|'',
 * }
 * @phpstan-type GroupResource array{
 *     id: mixed,
 *     admins_numbers: mixed,
 *     admins: mixed,
 *     admins_count: mixed,
 *     owner: mixed,
 *     owner_connected: mixed,
 *     clicks: mixed,
 *     communityUid: mixed,
 *     newsletterUid: mixed,
 *     description: mixed,
 *     deviceId: mixed,
 *     image: mixed,
 *     isFull: mixed,
 *     isApi: mixed,
 *     link: string|'',
 *     campaign_link: string|'',
 *     campaign_custom_domain: string|null,
 *     name: string,
 *     order: string,
 *     participants_numbers: string,
 *     participants_count: string,
 *     status: string,
 *     uid: string,
 *     updatedAt: string,
 *     createdAt: string,
 *     scheduled_message_count: string|0,
 *     type: string,
 *     manual_import: bool,
 *     origin: 1|0,
 *     lastSyncAt: string|null,
 * }
 * @phpstan-type IngestLeadScoreEventsRequest array{
 *     events: list<array{
 *         signal: 'group_join'|'group_leave'|'group_reaction'|'group_poll_vote'|'link_click',
 *         owner_user_id: int,
 *         contact_id?: int|null,
 *         phone?: string|null,
 *         occurred_at: string,
 *         external_id: string,
 *         scope_key?: string|null,
 *         meta?: array{
 *             campaign_message_uid?: string|null,
 *         }|null,
 *     }>,
 * }
 * @phpstan-type KanbanSettingResource array{
 *     id: int,
 *     user_id: int,
 *     name: string,
 *     mode: string,
 *     stagnant_days: int|null,
 *     value_custom_field_id: int|null,
 *     profit_custom_field_id: int|null,
 *     view_mode: string,
 *     icon: string|null,
 *     description: string|null,
 *     is_primary: bool,
 *     color_by_lead_score: bool,
 *     structure: list<mixed>,
 *     stages?: list<KanbanStageResource>,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type KanbanStageResource array{
 *     id: int,
 *     kanban_setting_id: int,
 *     name: string,
 *     position: int,
 *     contacts_count?: int,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type LeadScoreDistributionResource array{
 *     total: string,
 *     counts: array{
 *         hot: string,
 *         warm: string,
 *         cold: string,
 *         insufficient_data: string,
 *     },
 *     thresholds: array{
 *         hot_threshold: string,
 *         warm_threshold: string,
 *         min_signals: string,
 *     },
 * }
 * @phpstan-type LeadScoreEventResource array{
 *     id: string,
 *     signal: string,
 *     source: string,
 *     raw_points: string,
 *     applied_points: string,
 *     cap_reason: string|null,
 *     occurred_at: string|null,
 *     actor_user_id?: string,
 *     reason?: string,
 * }
 * @phpstan-type LeadScoreSettingsResource array{
 *     hot_threshold: int,
 *     warm_threshold: int,
 *     min_signals: int,
 *     hysteresis: int,
 *     decay_steps: list<array{
 *         days: int,
 *         points: int,
 *     }>,
 *     decay_enabled: bool,
 *     rules: list<mixed>,
 * }
 * @phpstan-type LengthAwarePaginator array{
 *     current_page: int,
 *     data: string,
 *     first_page_url: string,
 *     from: int|null,
 *     last_page: int,
 *     last_page_url: string,
 *     links: array<string, mixed>,
 *     next_page_url: string,
 *     path: string|null,
 *     per_page: int,
 *     prev_page_url: string|null,
 *     to: int|null,
 *     total: int,
 * }
 * @phpstan-type MessageOriginEnum 0|1|2|3
 * @phpstan-type MessageResponseResource array{
 *     message_template_id: string,
 *     block_type: string,
 *     group_name: string,
 *     responder_name: string,
 *     identity: string,
 *     identity_type: string,
 *     response: string,
 *     response_label: string,
 *     responded_at: string|null,
 * }
 * @phpstan-type MessageStatusEnum 0|1|2|3|4|5|6|7|8|9|10
 * @phpstan-type MessageTemplateResource array{
 *     id: string,
 *     type: string,
 *     value: string,
 *     metrics: string,
 *     reactions: string,
 *     votes: string,
 *     event_responses: string,
 *     shipping_order: string,
 * }
 * @phpstan-type MessageTemplateStatusEnum 'PENDING'|'APPROVED'|'REJECTED'|'PENDING_DELETION'|'PAUSED'|'DISABLED'|'ARCHIVED'
 * @phpstan-type MessageTemplateTypeEnum 'text'|'image'|'document'|'video'|'location'|'default'
 * @phpstan-type MessageTemplateVariationResource array{
 *     id: string,
 *     value: string,
 *     position: string,
 * }
 * @phpstan-type MessageTypeEnum 0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23
 * @phpstan-type MoveBroadcastsToFolderRequest array{
 *     broadcast_ids: list<int>,
 *     folder_id?: int|null,
 * }
 * @phpstan-type MoveContactStageRequest array{
 *     contact_id: int,
 *     stage_id: int,
 * }
 * @phpstan-type NodeResource array{
 *     uuid: string,
 *     flow_id: string,
 *     type: int,
 *     action: string,
 *     value: string,
 *     next_uuid: string,
 * }
 * @phpstan-type OperationPlanResource array{
 *     id: int,
 *     uuid: string,
 *     campaign_id: int,
 *     pipeline_uuid: string|null,
 *     status: string,
 *     name: string,
 *     timezone: string,
 *     window_start: string,
 *     window_end: string,
 *     range_start_date: string,
 *     range_end_date: string,
 *     daily_cap: int,
 *     min_spacing_minutes: int,
 *     confirmation_spacing_minutes: int,
 *     entry_point: string,
 *     seed: int|null,
 *     idempotency_key: string|null,
 *     confirmed_at: string|null,
 *     operations: list<string>,
 * }
 * @phpstan-type OperationPlanSummaryResource array{
 *     id: string,
 *     uuid: string,
 *     campaign_id: string,
 *     pipeline_uuid: string,
 *     status: string,
 *     name: string,
 *     entry_point: string,
 *     timezone: string,
 *     range_start_date: string|null,
 *     range_end_date: string|null,
 *     daily_cap: string,
 *     confirmed_at: string|null,
 *     operation_counts: array{
 *         total: float|int,
 *         pending: float|int|list<mixed>,
 *         running: string|0,
 *         done: string|0,
 *         failed: int,
 *         skipped: string|0,
 *         cancelled: string|0,
 *     },
 * }
 * @phpstan-type ParticipantEventResource array{
 *     phone: string,
 *     whatsapp_id: string,
 *     event_type: 'join'|'leave',
 *     event_at: string|null,
 *     group_id: int,
 *     group_name: string,
 *     country_code: string,
 *     country_name: string,
 * }
 * @phpstan-type ParticipantResource array{
 *     id: string,
 *     phone: string,
 *     action: string,
 *     isAdmin: string,
 *     created_at: string|'',
 * }
 * @phpstan-type PaymentEventTypeEnum 'approved'|'pending'|'rejected'|'cancelled'|'refunded'|'chargeback'
 * @phpstan-type PaymentProductResource array{
 *     external_product_id: string,
 *     name: string|null,
 * }
 * @phpstan-type QuickReplyResource array{
 *     id: int,
 *     name: string,
 *     message: string,
 *     file: string,
 *     type: MessageTypeEnum,
 *     visibility: QuickReplyVisibilityEnum,
 *     agents: array<string, mixed>,
 *     user_id: int,
 *     created_at: string,
 *     updated_at: string,
 * }
 * @phpstan-type QuickReplyVisibilityEnum 1|2|3
 * @phpstan-type RemoveContactStageRequest array{
 *     contact_id: int,
 * }
 * @phpstan-type ReorderKanbanStagesRequest array{
 *     stage_ids: list<int>,
 * }
 * @phpstan-type SaleResource array{
 *     id: int,
 *     occurred_at: string,
 *     platform: string,
 *     product_name: string|null,
 *     kind: string,
 *     event_type: string|null,
 *     amount_minor: int,
 *     currency: string,
 *     attribution: string,
 *     buyer: array{
 *         contact_id: int|null,
 *         name: string|null,
 *         phone: string|null,
 *     }|null,
 * }
 * @phpstan-type SalesMetricsResource array{
 *     window: string,
 *     currencies: string,
 *     distinct_buyers: string,
 *     repeat_purchase_rate: string,
 * }
 * @phpstan-type ScheduledMessageFolderResource array{
 *     id: string,
 *     name: string,
 *     scheduled_messages_count: int,
 * }
 * @phpstan-type ScheduledMessageIndexResource array{
 *     id: string,
 *     name: string|'',
 *     send_at: string|null,
 *     status: string,
 *     is_draft: string|bool,
 *     type: string,
 *     folder_id: string,
 *     campaign_ids: string,
 *     excluded_group_ids: string|list<string>,
 *     can_be_edited: string,
 *     recurrence: array{
 *         enabled: bool,
 *         frequency: string,
 *         interval: string,
 *         week_days: string,
 *         end_type: string,
 *         end_date: string,
 *         occurrences: string,
 *         current_occurrence: string,
 *         only_new_groups: string,
 *         only_full_groups: string,
 *     }|array{
 *         enabled: bool,
 *     },
 * }
 * @phpstan-type ScheduledMessageResource array{
 *     id: string,
 *     name: string,
 *     send_at: string,
 *     status: string,
 *     type: string,
 *     audience: string,
 *     total_readed: string,
 *     total_played: string,
 *     campaigns: string,
 *     groups: string,
 *     total_groups: string,
 *     messages: list<MessageTemplateResource>,
 * }
 * @phpstan-type ScheduledMessageRunsResource array{
 *     run: string,
 *     total_groups: int,
 *     count_ok: int,
 *     count_error: int,
 * }
 * @phpstan-type SendMessageRequest array{
 *     phone?: string,
 *     whatsapp_id?: string,
 *     device_id: int,
 *     text?: string,
 *     contact_id: int,
 *     file?: string,
 *     temporal_uuid?: string,
 *     note?: bool,
 *     options?: list<array{
 *         id?: string,
 *         label?: string,
 *     }>,
 *     reference_message_uid?: string|null,
 *     link_preview?: bool,
 *     link_preview_meta?: array{
 *         url?: string,
 *         title?: string|null,
 *         description?: string|null,
 *         image_url?: string|null,
 *         domain?: string,
 *     },
 *     client_reference?: PendingClientReference,
 *     file_type?: 'image'|'video'|'audio'|'document'|'sticker'|null,
 *     file_name?: string|null,
 * }
 * @phpstan-type SslValidationMethod 'TXT'|'HTTP'
 * @phpstan-type StoreAlertDestinationRequest array{
 *     type: AlertDestinationType,
 *     destination: string,
 *     name?: string|null,
 * }
 * @phpstan-type StoreBroadcastFolderRequest array{
 *     name: string,
 * }
 * @phpstan-type StoreContactCardRequest array{
 *     device_id: int,
 *     contact_card_ids: list<int>,
 *     client_reference?: PendingClientReference,
 * }
 * @phpstan-type StoreContactNoteRequest array{
 *     body: string,
 * }
 * @phpstan-type StoreContactRequest array{
 *     phone?: string,
 *     whatsapp_id?: string,
 *     name?: string|null,
 *     email?: string|null,
 *     image?: string|null,
 *     deviceId?: int|null,
 * }
 * @phpstan-type StoreCustomDomainRequest array{
 *     hostname: string,
 * }
 * @phpstan-type StoreDisconnectReasonRequest array{
 *     reason: '1'|'2'|'4'|'5',
 * }
 * @phpstan-type StoreEventRequest array{
 *     device_id: int,
 *     name: string,
 *     date_time: string,
 *     description?: string|null,
 *     location?: string|null,
 * }
 * @phpstan-type StoreFolderRequest array{
 *     name: string,
 *     parent_id?: int|null,
 * }
 * @phpstan-type StoreKanbanSettingRequest array{
 *     name: string,
 *     mode?: 'tags'|'stages',
 *     structure?: list<array{
 *         tag_id: int,
 *         position: int,
 *     }>|null,
 *     stages?: list<array{
 *         name?: string,
 *     }>|null,
 *     stagnant_days?: int|null,
 *     view_mode?: 'compact'|'extended'|null,
 *     icon?: 'target'|'filter'|'trending-up'|'users'|'briefcase'|'shopping-cart'|'heart'|'star'|'flag'|'rocket'|'layers'|'zap'|null,
 *     description?: string|null,
 *     is_primary?: bool,
 *     color_by_lead_score?: bool,
 * }
 * @phpstan-type StoreKanbanStageRequest array{
 *     name: string,
 *     after_stage_id?: int|null,
 * }
 * @phpstan-type StoreLeadScoreAdjustmentRequest array{
 *     delta: int,
 *     reason: string,
 * }
 * @phpstan-type StoreMessageReactionRequest array{
 *     emoji: string,
 * }
 * @phpstan-type StoreMessageTemplateRequest array{
 *     name: string,
 *     language_code: 'af'|'sq'|'ar'|'ar_EG'|'ar_AE'|'ar_LB'|'ar_MA'|'ar_QA'|'az'|'be_BY'|'bn'|'bn_IN'|'bg'|'ca'|'zh_CN'|'zh_HK'|'zh_TW'|'hr'|'cs'|'da'|'prs_AF'|'nl'|'nl_BE'|'en'|'en_GB'|'en_US'|'en_AE'|'en_AU'|'en_CA'|'en_GH'|'en_IE'|'en_IN'|'en_JM'|'en_MY'|'en_NZ'|'en_QA'|'en_SG'|'en_UG'|'en_ZA'|'et'|'fil'|'fi'|'fr'|'fr_BE'|'fr_CA'|'fr_CH'|'fr_CI'|'fr_MA'|'ka'|'de'|'de_AT'|'de_CH'|'el'|'gu'|'ha'|'he'|'hi'|'hu'|'id'|'ga'|'it'|'ja'|'kn'|'kk'|'rw_RW'|'ko'|'ky_KG'|'lo'|'lv'|'lt'|'mk'|'ms'|'ml'|'mr'|'nb'|'ps_AF'|'fa'|'pl'|'pt_BR'|'pt_PT'|'pa'|'ro'|'ru'|'sr'|'si_LK'|'sk'|'sl'|'es'|'es_AR'|'es_CL'|'es_CO'|'es_CR'|'es_DO'|'es_EC'|'es_HN'|'es_MX'|'es_PA'|'es_PE'|'es_ES'|'es_UY'|'sw'|'sv'|'ta'|'te'|'th'|'tr'|'uk'|'ur'|'uz'|'vi'|'zu',
 *     category: 'UTILITY'|'MARKETING'|'AUTHENTICATION',
 *     type: 'text'|'image'|'document'|'video'|'location'|'default',
 *     header?: string|null,
 *     file?: string,
 *     body: string,
 *     params?: list<string>|null,
 *     footer?: string|null,
 *     buttons?: list<array{
 *         text?: string,
 *         type?: 'QUICK_REPLY'|'URL'|'PHONE_NUMBER',
 *         url?: string,
 *         phone_number?: string,
 *     }>|null,
 * }
 * @phpstan-type StorePollRequest array{
 *     device_id: int,
 *     question: string,
 *     options: list<string>,
 *     multiple?: bool,
 * }
 * @phpstan-type StoreWebhookEndpointRequest array{
 *     url: string,
 *     description?: string|null,
 *     events?: list<'device.disconnected'|'device.connected'|'billing.payment_failed'|'billing.plan_blocked'|'billing.upcoming_charge'|'subscription.expiring'|'subscription.trial_ending'|'subscription.reactivated'|'subscription.cancellation_confirmed'|'subscription.dormant_ending'|'ai_quota.low_balance'|'ai_quota.topup_succeeded'|'ai_quota.topup_failed'|'mac.usage_80'|'mac.usage_100'|'mac.expiring'|'account.blocked'>,
 * }
 * @phpstan-type SwitchValidationMethodRequest array{
 *     method: SslValidationMethod,
 * }
 * @phpstan-type TagResource array{
 *     id: int,
 *     name: string,
 *     description: string,
 *     color: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 *     contact_count: string|int,
 * }
 * @phpstan-type TemplateResource array{
 *     id: int,
 *     name: string,
 *     text: string,
 *     header: string,
 *     footer: string,
 *     observation: string,
 *     uid: string,
 *     category: string,
 *     type: MessageTemplateTypeEnum|'',
 *     status: MessageTemplateStatusEnum|'',
 *     language_code: string,
 *     upload_session_id: string,
 *     header_handle: string,
 *     file: string,
 *     params: list<mixed>|'',
 *     buttons: list<mixed>|'',
 *     user_id: int,
 *     device_id: int,
 *     created_at: string|null,
 *     quick_reply_buttons: list<mixed>,
 *     waba_id: string|null,
 *     belongs_to_current_device: bool,
 * }
 * @phpstan-type Ticket array{
 *     id: int,
 *     user_id: int,
 *     contact_id: int|null,
 *     title: string,
 *     description: string|null,
 *     deadline: int|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type UpdateAlertDailyReportRequest array{
 *     enabled: bool,
 *     send_time?: string|null,
 *     all_campaigns: bool,
 *     campaign_ids?: list<int>|null,
 * }
 * @phpstan-type UpdateAlertDestinationRequest array{
 *     name?: string|null,
 *     destination?: string,
 * }
 * @phpstan-type UpdateAlertQuietHourRequest array{
 *     enabled: bool,
 *     start_time?: string|null,
 *     end_time?: string|null,
 * }
 * @phpstan-type UpdateBroadcastFolderRequest array{
 *     name: string,
 * }
 * @phpstan-type UpdateContactRequest array{
 *     name?: string|null,
 *     email?: string|null,
 *     phone?: string|null,
 *     notes?: string|null,
 *     status?: int,
 *     adviser_id?: int|null,
 *     active_conversation?: bool,
 *     favorite?: bool,
 *     fixed?: bool,
 *     image?: string|null,
 *     can_execute_flows?: bool,
 *     manually_unread?: bool,
 * }
 * @phpstan-type UpdateFolderRequest array{
 *     name?: string,
 *     parent_id?: int|null,
 * }
 * @phpstan-type UpdateKanbanSettingRequest array{
 *     name?: string|null,
 *     mode?: string,
 *     structure?: list<array{
 *         tag_id: int,
 *         position: int,
 *     }>|null,
 *     stagnant_days?: int|null,
 *     view_mode?: 'compact'|'extended'|null,
 *     icon?: 'target'|'filter'|'trending-up'|'users'|'briefcase'|'shopping-cart'|'heart'|'star'|'flag'|'rocket'|'layers'|'zap'|null,
 *     description?: string|null,
 *     is_primary?: bool,
 *     color_by_lead_score?: bool,
 * }
 * @phpstan-type UpdateKanbanStageRequest array{
 *     name: string,
 * }
 * @phpstan-type UpdateMessageTemplateRequest array{
 *     device_id: int,
 *     category: 'UTILITY'|'MARKETING'|'AUTHENTICATION',
 *     type: 'text'|'image'|'document'|'video'|'location'|'default',
 *     header?: string|null,
 *     file?: string,
 *     body: string,
 *     params?: list<string>|null,
 *     footer?: string|null,
 *     buttons?: list<array{
 *         text?: string,
 *         type?: 'QUICK_REPLY'|'URL'|'PHONE_NUMBER',
 *         url?: string,
 *         phone_number?: string,
 *     }>|null,
 * }
 * @phpstan-type UpdateWebhookEndpointEventsRequest array{
 *     events: list<array{
 *         event_type: 'device.disconnected'|'device.connected'|'billing.payment_failed'|'billing.plan_blocked'|'billing.upcoming_charge'|'subscription.expiring'|'subscription.trial_ending'|'subscription.reactivated'|'subscription.cancellation_confirmed'|'subscription.dormant_ending'|'ai_quota.low_balance'|'ai_quota.topup_succeeded'|'ai_quota.topup_failed'|'mac.usage_80'|'mac.usage_100'|'mac.expiring'|'account.blocked',
 *         enabled?: bool,
 *     }>,
 * }
 * @phpstan-type UpdateWebhookEndpointRequest array{
 *     url?: string,
 *     description?: string|null,
 *     status?: 'enabled'|'disabled',
 * }
 * @phpstan-type UpsertAlertSettingRequest array{
 *     module: AlertModule,
 *     action: AlertAction,
 *     enabled: bool,
 * }
 * @phpstan-type VerifyAlertOtpRequest array{
 *     code: string,
 * }
 * @phpstan-type WabaCapiDatasetResource array{
 *     waba_id: string,
 *     dataset_id: string|null,
 *     scope_ok: bool,
 *     consented_at: string|null,
 *     last_verified_at: string|null,
 * }
 * @phpstan-type WebhookDeliveryResource array{
 *     id: int,
 *     event_id: string,
 *     event_type: string,
 *     attempt: int,
 *     url: string,
 *     response_status: int|null,
 *     succeeded: bool,
 *     error: string|null,
 *     duration_ms: int|null,
 *     attempted_at: string,
 * }
 * @phpstan-type WebhookEndpointResource array{
 *     id: int,
 *     url: string,
 *     description: string|null,
 *     status: string,
 *     disabled_reason: string|null,
 *     events?: list<array{
 *         event_type: string,
 *         enabled: bool,
 *     }>,
 *     health: array{
 *         consecutive_failures: int,
 *         last_delivery_at: string|null,
 *         last_success_at: string|null,
 *     },
 *     secret_rotated_at: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type WhatsAppProviderEnum 1|2|3|4
 * @phpstan-type WhatsappCostSummaryResource array<string, mixed>
 * @phpstan-type PendingClientReference string
 * @phpstan-type PendingWebhookSubscriptionFilters array{
 *     campaign_ids?: list<string>,
 * }
 * @phpstan-type PendingWebhookSubscription array{
 *     id: string,
 *     url: string,
 *     description: string|null,
 *     events: list<string>,
 *     filters: PendingWebhookSubscriptionFilters,
 *     status: 'active'|'paused'|'disabled',
 *     disabled_reason: 'too_many_failures'|'key_revoked'|null,
 *     last_delivery_at: string|null,
 *     last_success_at: string|null,
 *     secret_rotated_at: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 *     migration: array{
 *         origin: string,
 *         status: string,
 *         activated_via: string|null,
 *         activated_at: string|null,
 *         last_result: mixed,
 *     }|null,
 * }
 * @phpstan-type PendingWebhookSubscriptionWithSecret array{
 *     id: string,
 *     url: string,
 *     description: string|null,
 *     events: list<string>,
 *     filters: PendingWebhookSubscriptionFilters,
 *     status: 'active'|'paused'|'disabled',
 *     disabled_reason: 'too_many_failures'|'key_revoked'|null,
 *     last_delivery_at: string|null,
 *     last_success_at: string|null,
 *     secret_rotated_at: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 *     migration: array{
 *         origin: string,
 *         status: string,
 *         activated_via: string|null,
 *         activated_at: string|null,
 *         last_result: mixed,
 *     }|null,
 *     secret: string,
 *     secret_notice: string,
 * }
 * @phpstan-type PendingWebhookSubscriptionCreate array{
 *     url: string,
 *     description?: string|null,
 *     events: list<string>,
 *     filters?: PendingWebhookSubscriptionFilters|null,
 * }
 * @phpstan-type PendingWebhookSubscriptionUpdate array{
 *     url: string,
 *     description?: string|null,
 *     events: list<string>,
 *     filters?: PendingWebhookSubscriptionFilters|null,
 *     status?: 'active'|'paused',
 * }
 * @phpstan-type PendingWebhookSubscriptionDelivery array{
 *     id: int,
 *     event_id: string,
 *     event_type: string,
 *     attempt: int,
 *     url: string,
 *     response_status: int|null,
 *     succeeded: bool,
 *     error: string|null,
 *     duration_ms: int|null,
 *     attempted_at: string|null,
 * }
 * @phpstan-type PendingWebhookSubscriptionTestResult array{
 *     succeeded: bool,
 *     response_status: int|null,
 *     error: string|null,
 *     duration_ms: int|null,
 *     delivery_id: int,
 * }
 * @phpstan-type PendingPublicMessage array{
 *     id: int,
 *     contact_id: int|null,
 *     device_id: int|null,
 *     direction: 'outbound'|'inbound',
 *     type: 'text'|'image'|'video'|'document'|'sticker'|'contact'|'audio'|'note'|'action'|'invalid'|'interactive'|'location'|'voice_call'|'video_call'|'event'|'button'|'template'|'notification'|'option_list'|'template_button_reply'|'poll'|'whatsapp_form'|'call_permission_request'|'call_permission_reply'|'request_contact_info',
 *     status: 'queued'|'sent'|'delivered'|'read'|'failed'|null,
 *     error: PendingMessageError|null,
 *     client_reference: string|null,
 *     created_at: string|null,
 *     updated_at: string|null,
 * }
 * @phpstan-type PendingForwardResult array{
 *     contact_id: int,
 *     mode: 'internal'|'native',
 *     status: 'queued'|'blocked',
 *     reason: 'mac_lockout_24h'|'mac_limit_reached'|'device_unavailable'|'not_sent'|'target_not_found'|null,
 *     message: PendingPublicMessage|null,
 * }
 * @phpstan-type PendingMessage array{
 *     message: string,
 * }
 * @phpstan-type PendingDeviceCapabilities array{
 *     messages: array{
 *         text: bool,
 *         media: bool,
 *         buttons: bool,
 *         lists: bool,
 *         templates: bool,
 *         location: bool,
 *         voice_note: bool,
 *         polls: bool,
 *         events: bool,
 *         contact_card: bool,
 *         reactions: bool,
 *     },
 *     groups: array{
 *         create: bool,
 *         add_participants: 'direct'|'invite'|false,
 *         remove_participants: bool,
 *         admins: bool,
 *         photo: bool,
 *         settings: bool,
 *         invite_link: bool,
 *         send: bool,
 *         join_requests: 'list'|'list_and_decide'|false,
 *     },
 *     statuses: list<'sent'|'delivered'|'read'|'failed'>,
 * }
 * @phpstan-type PendingMessageError array{
 *     code: int|null,
 *     title: string|null,
 *     reason: 'device_unavailable'|'insufficient_balance'|'window_closed'|'recipient_unreachable'|'template_not_found'|'template_unresolved_placeholder'|'not_queued'|'not_sent'|null,
 * }
 * @phpstan-type PendingFormLintIssue array{
 *     code: string,
 *     severity: string,
 *     screenId: string|null,
 *     itemId: string|null,
 *     message: string,
 * }
 * @phpstan-type PendingFormNumber array{
 *     device_id: int|null,
 *     primary: bool,
 *     status: 'draft'|'published'|'publishing'|'failed',
 *     error: string|null,
 * }
 * @phpstan-type PendingForm array{
 *     id: int,
 *     family_id: int,
 *     name: string,
 *     label: string|null,
 *     category: string|null,
 *     status: 'draft'|'published'|'deprecated',
 *     version: int,
 *     has_endpoint: bool,
 *     first_screen: string|null,
 *     first_screen_dynamic: bool,
 *     lint_errors_count: int,
 *     meta_flow_id: string|null,
 *     published_at: string|null,
 *     updated_at: string,
 *     definition?: array<string, mixed>,
 *     fields?: list<mixed>,
 *     mapping?: mixed,
 *     tag_rules?: mixed,
 *     flow_json?: mixed,
 *     validation_errors?: mixed,
 *     sends?: int,
 *     responses?: int,
 *     draft_id?: int,
 *     draft_version?: int,
 * }
 * @phpstan-type PendingExternalReference string|null
 * @phpstan-type PendingConnectionType 'qr'|'coexistence'|'dedicated'|null
 * @phpstan-type PendingDeviceCreate array{
 *     connection: 'qr',
 *     alias?: string|null,
 *     external_reference?: PendingExternalReference,
 *     same_number_as?: int|null,
 *     country?: string|null,
 * }
 * @phpstan-type PendingDeviceQr array{
 *     connected: bool,
 *     qr_code: string|null,
 *     expires_in: int,
 * }
 * @phpstan-type PendingConnectLinkErrorCode 'link_expired'|'already_used'|'revoked'|'quota_exceeded'|'trial_number_blocked'|'facebook_auth_failed'|'meta_error'|'number_not_owned'|'qr_timeout'|'provider_error'|null
 * @phpstan-type PendingConnectLinkCreate array{
 *     type: 'meta'|'qr',
 *     allowed_connection_types?: list<'coexistence'|'dedicated'>,
 *     provision_us_number?: bool,
 *     device_id?: int|null,
 *     same_number_as?: int|null,
 *     country?: string|null,
 *     external_reference?: PendingExternalReference,
 *     return_url?: string|null,
 *     expires_in_minutes?: int,
 * }
 * @phpstan-type PendingConnectLink array{
 *     id: int,
 *     type: 'meta'|'qr',
 *     status: 'pending'|'completed'|'failed'|'expired'|'revoked',
 *     url?: string,
 *     expires_at: string,
 *     allowed_connection_types: list<'coexistence'|'dedicated'>|null,
 *     provision_us_number: bool,
 *     device_id: int|null,
 *     external_reference: PendingExternalReference,
 *     country: string|null,
 *     return_url: string|null,
 *     error_code: PendingConnectLinkErrorCode,
 *     created_at: string,
 *     completed_at: string|null,
 * }
 * @phpstan-type PendingWebhookDevice array{
 *     id: int,
 *     name: string|null,
 *     'number': string|null,
 *     connection?: PendingConnectionType,
 *     external_reference?: PendingExternalReference,
 * }
 * @phpstan-type PendingConnectLinkId int|null
 * @phpstan-type PendingDeviceHealthState 'available'|'limited'|'blocked'|'unknown'
 */
final class Schemas
{
    private function __construct() {}
}
