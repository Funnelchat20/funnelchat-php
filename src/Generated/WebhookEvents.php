<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Generated;

/**
 * The webhook events of the spec: the catalog and the type of each delivery.
 *
 * `WebhookEvent` is what `Webhook::constructEvent()` returns: the envelope every delivery shares.
 * Its `type` is a string, because new types can arrive before this SDK knows them. Once you
 * checked `type`, the `XxxEvent` alias of that type (or `XxxData` for its `data`) gives the shape:
 * `@phpstan-import-type MessageReceivedEvent from \Funnelchat\Generated\WebhookEvents`.
 * When the spec only has an example, the type is inferred from it: keys in the example are
 * required and `null` values are `mixed`. `WebhookEventType` is every type the spec knows.
 *
 * @phpstan-import-type PendingDeviceHealthState from Schemas
 * @phpstan-import-type PendingMessageError from Schemas
 * @phpstan-import-type PendingWebhookDevice from Schemas
 *
 * @phpstan-type AccountBlockedData array{
 *     reason: string,
 *     grace_started_at: string,
 *     grace_expired_at: string,
 * }
 * @phpstan-type AccountBlockedEvent array{
 *     id: string,
 *     type: 'account.blocked',
 *     version: int,
 *     occurred_at: string,
 *     data: AccountBlockedData,
 * }
 * @phpstan-type AiQuotaLowBalanceData array<string, mixed>
 * @phpstan-type AiQuotaLowBalanceEvent array{
 *     id: string,
 *     type: 'ai_quota.low_balance',
 *     version: int,
 *     occurred_at: string,
 *     data: AiQuotaLowBalanceData,
 * }
 * @phpstan-type AiQuotaTopupFailedData array<string, mixed>
 * @phpstan-type AiQuotaTopupFailedEvent array{
 *     id: string,
 *     type: 'ai_quota.topup_failed',
 *     version: int,
 *     occurred_at: string,
 *     data: AiQuotaTopupFailedData,
 * }
 * @phpstan-type AiQuotaTopupSucceededData array<string, mixed>
 * @phpstan-type AiQuotaTopupSucceededEvent array{
 *     id: string,
 *     type: 'ai_quota.topup_succeeded',
 *     version: int,
 *     occurred_at: string,
 *     data: AiQuotaTopupSucceededData,
 * }
 * @phpstan-type BillingPaymentFailedData array{
 *     amount_cents: int,
 *     currency: string,
 * }
 * @phpstan-type BillingPaymentFailedEvent array{
 *     id: string,
 *     type: 'billing.payment_failed',
 *     version: int,
 *     occurred_at: string,
 *     data: BillingPaymentFailedData,
 * }
 * @phpstan-type BillingPlanBlockedData array<string, mixed>
 * @phpstan-type BillingPlanBlockedEvent array{
 *     id: string,
 *     type: 'billing.plan_blocked',
 *     version: int,
 *     occurred_at: string,
 *     data: BillingPlanBlockedData,
 * }
 * @phpstan-type BillingUpcomingChargeData array{
 *     charge_date: string,
 *     amount_cents: int,
 *     currency: string,
 * }
 * @phpstan-type BillingUpcomingChargeEvent array{
 *     id: string,
 *     type: 'billing.upcoming_charge',
 *     version: int,
 *     occurred_at: string,
 *     data: BillingUpcomingChargeData,
 * }
 * @phpstan-type BroadcastFinishedData array{
 *     broadcast: array{
 *         id: int,
 *         name: string,
 *     },
 *     status: string,
 *     reason: string,
 *     totals: array{
 *         processed: int,
 *         failed: int,
 *         skipped_unreachable: int,
 *     },
 * }
 * @phpstan-type BroadcastFinishedEvent array{
 *     id: string,
 *     type: 'broadcast.finished',
 *     version: int,
 *     occurred_at: string,
 *     data: BroadcastFinishedData,
 * }
 * @phpstan-type BroadcastPausedData array{
 *     broadcast: array{
 *         id: int,
 *         name: string,
 *     },
 *     reason: string,
 *     quality: string,
 *     quality_baseline: string,
 * }
 * @phpstan-type BroadcastPausedEvent array{
 *     id: string,
 *     type: 'broadcast.paused',
 *     version: int,
 *     occurred_at: string,
 *     data: BroadcastPausedData,
 * }
 * @phpstan-type CampaignNoGroupsAvailableData array{
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     reason: string,
 * }
 * @phpstan-type CampaignNoGroupsAvailableEvent array{
 *     id: string,
 *     type: 'campaign.no_groups_available',
 *     version: int,
 *     occurred_at: string,
 *     data: CampaignNoGroupsAvailableData,
 * }
 * @phpstan-type CampaignStatusChangedData array{
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     from: string,
 *     to: string,
 * }
 * @phpstan-type CampaignStatusChangedEvent array{
 *     id: string,
 *     type: 'campaign.status_changed',
 *     version: int,
 *     occurred_at: string,
 *     data: CampaignStatusChangedData,
 * }
 * @phpstan-type ContactCreatedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *         email: string,
 *     },
 * }
 * @phpstan-type ContactCreatedEvent array{
 *     id: string,
 *     type: 'contact.created',
 *     version: int,
 *     occurred_at: string,
 *     data: ContactCreatedData,
 * }
 * @phpstan-type ContactStageChangedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     board: array{
 *         id: int,
 *         name: string,
 *     },
 *     kind: string,
 *     from_stage: array{
 *         id: int,
 *         name: string,
 *         position: int,
 *     },
 *     to_stage: array{
 *         id: int,
 *         name: string,
 *         position: int,
 *     },
 * }
 * @phpstan-type ContactStageChangedEvent array{
 *     id: string,
 *     type: 'contact.stage_changed',
 *     version: int,
 *     occurred_at: string,
 *     data: ContactStageChangedData,
 * }
 * @phpstan-type ContactTagAddedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     tag: array{
 *         id: int,
 *         name: string,
 *     },
 *     source: string,
 * }
 * @phpstan-type ContactTagAddedEvent array{
 *     id: string,
 *     type: 'contact.tag_added',
 *     version: int,
 *     occurred_at: string,
 *     data: ContactTagAddedData,
 * }
 * @phpstan-type ContactTagRemovedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     tag: array{
 *         id: int,
 *         name: string,
 *     },
 *     source: string,
 * }
 * @phpstan-type ContactTagRemovedEvent array{
 *     id: string,
 *     type: 'contact.tag_removed',
 *     version: int,
 *     occurred_at: string,
 *     data: ContactTagRemovedData,
 * }
 * @phpstan-type ConversationAssignedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     assignee: array{
 *         type: string,
 *         name: string,
 *     },
 * }
 * @phpstan-type ConversationAssignedEvent array{
 *     id: string,
 *     type: 'conversation.assigned',
 *     version: int,
 *     occurred_at: string,
 *     data: ConversationAssignedData,
 * }
 * @phpstan-type ConversationClosedData array{
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 * }
 * @phpstan-type ConversationClosedEvent array{
 *     id: string,
 *     type: 'conversation.closed',
 *     version: int,
 *     occurred_at: string,
 *     data: ConversationClosedData,
 * }
 * @phpstan-type DailyReportGeneratedData array{
 *     period: array{
 *         date: string,
 *         timezone: string,
 *     },
 *     clicks: int,
 *     joins: int,
 *     leaves: int,
 *     conversion_rate: float|int,
 * }
 * @phpstan-type DailyReportGeneratedEvent array{
 *     id: string,
 *     type: 'daily_report.generated',
 *     version: int,
 *     occurred_at: string,
 *     data: DailyReportGeneratedData,
 * }
 * @phpstan-type DeviceConnectedData array{
 *     device: PendingWebhookDevice,
 *     connected_at: string,
 *     connect_link_id: int|string|null,
 * }
 * @phpstan-type DeviceConnectedEvent array{
 *     id: string,
 *     type: 'device.connected',
 *     version: int,
 *     occurred_at: string,
 *     data: DeviceConnectedData,
 * }
 * @phpstan-type DeviceDeletedData array{
 *     device: PendingWebhookDevice,
 *     deleted_at: string,
 * }
 * @phpstan-type DeviceDeletedEvent array{
 *     id: string,
 *     type: 'device.deleted',
 *     version: int,
 *     occurred_at: string,
 *     data: DeviceDeletedData,
 * }
 * @phpstan-type DeviceDisconnectedData array{
 *     device: PendingWebhookDevice,
 *     disconnected_at: string,
 *     reason: 'qr_logged_out'|'meta_account_not_registered'|'shadow_ban_protection'|'provider_error'|'unknown',
 * }
 * @phpstan-type DeviceDisconnectedEvent array{
 *     id: string,
 *     type: 'device.disconnected',
 *     version: int,
 *     occurred_at: string,
 *     data: DeviceDisconnectedData,
 * }
 * @phpstan-type DeviceHealthChangedData array{
 *     device: PendingWebhookDevice,
 *     previous_state: PendingDeviceHealthState,
 *     state: PendingDeviceHealthState,
 *     changed_at: string,
 * }
 * @phpstan-type DeviceHealthChangedEvent array{
 *     id: string,
 *     type: 'device.health_changed',
 *     version: int,
 *     occurred_at: string,
 *     data: DeviceHealthChangedData,
 * }
 * @phpstan-type FlowCompletedData array{
 *     flow: array{
 *         id: int,
 *         name: string,
 *     },
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 * }
 * @phpstan-type FlowCompletedEvent array{
 *     id: string,
 *     type: 'flow.completed',
 *     version: int,
 *     occurred_at: string,
 *     data: FlowCompletedData,
 * }
 * @phpstan-type FlowFailedData array{
 *     flow: array{
 *         id: int,
 *         name: string,
 *     },
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     reason: string,
 * }
 * @phpstan-type FlowFailedEvent array{
 *     id: string,
 *     type: 'flow.failed',
 *     version: int,
 *     occurred_at: string,
 *     data: FlowFailedData,
 * }
 * @phpstan-type GroupBlockedData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *     },
 *     reason: string,
 * }
 * @phpstan-type GroupBlockedEvent array{
 *     id: string,
 *     type: 'group.blocked',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupBlockedData,
 * }
 * @phpstan-type GroupCreatedData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *         type: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     source: string,
 * }
 * @phpstan-type GroupCreatedEvent array{
 *     id: string,
 *     type: 'group.created',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupCreatedData,
 * }
 * @phpstan-type GroupFullData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *         type: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     trigger: string,
 *     participants: int,
 * }
 * @phpstan-type GroupFullEvent array{
 *     id: string,
 *     type: 'group.full',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupFullData,
 * }
 * @phpstan-type GroupInviteLinkDownData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *         type: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     reason: string,
 *     renewal_attempted: bool,
 * }
 * @phpstan-type GroupInviteLinkDownEvent array{
 *     id: string,
 *     type: 'group.invite_link_down',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupInviteLinkDownData,
 * }
 * @phpstan-type GroupInviteLinkRenewedData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *         type: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     invite_link: string,
 *     previous_invite_link: string,
 *     trigger: string,
 *     recovered: bool,
 * }
 * @phpstan-type GroupInviteLinkRenewedEvent array{
 *     id: string,
 *     type: 'group.invite_link_renewed',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupInviteLinkRenewedData,
 * }
 * @phpstan-type GroupMessageReceivedData array{
 *     group: array{
 *         uid: string,
 *         name: string|null,
 *     },
 *     message: array{
 *         uid: string,
 *         type: 'text'|'image'|'audio'|'video'|'document'|'poll'|'event'|'sticker'|'contact'|'location'|'system'|'unsupported'|'view_once',
 *         text: string|null,
 *         caption: string|null,
 *         media_url: string|null,
 *         mime_type: string|null,
 *         file_name: string|null,
 *         quoted_uid: string|null,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: 'phone'|'whatsapp_user',
 *         name: string|null,
 *     },
 * }
 * @phpstan-type GroupMessageReceivedEvent array{
 *     id: string,
 *     type: 'group.message_received',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupMessageReceivedData,
 * }
 * @phpstan-type GroupReactionAddedData array{
 *     group: array{
 *         uid: string,
 *         name: string|null,
 *     },
 *     message: array{
 *         uid: string,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: 'phone'|'whatsapp_user',
 *         name: string|null,
 *     },
 *     reaction: string,
 * }
 * @phpstan-type GroupReactionAddedEvent array{
 *     id: string,
 *     type: 'group.reaction_added',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupReactionAddedData,
 * }
 * @phpstan-type GroupRecoveredData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *     },
 *     reason: string,
 * }
 * @phpstan-type GroupRecoveredEvent array{
 *     id: string,
 *     type: 'group.recovered',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupRecoveredData,
 * }
 * @phpstan-type GroupActionFailedData array{
 *     campaign: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     pipeline: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     action: array{
 *         type: string,
 *     },
 *     reason: string,
 * }
 * @phpstan-type GroupActionFailedEvent array{
 *     id: string,
 *     type: 'group_action.failed',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupActionFailedData,
 * }
 * @phpstan-type GroupJoinRequestCreatedData array{
 *     group: array{
 *         uid: string,
 *         name: string|null,
 *     },
 *     participant: array{
 *         identity: string|null,
 *         identity_type: 'phone'|'whatsapp_user'|'unknown',
 *         name: string|null,
 *     },
 * }
 * @phpstan-type GroupJoinRequestCreatedEvent array{
 *     id: string,
 *     type: 'group_join_request.created',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupJoinRequestCreatedData,
 * }
 * @phpstan-type GroupJoinRequestRevokedData array{
 *     group: array{
 *         uid: string,
 *         name: string|null,
 *     },
 *     participant: array{
 *         identity: string|null,
 *         identity_type: 'phone'|'whatsapp_user'|'unknown',
 *         name: string|null,
 *     },
 * }
 * @phpstan-type GroupJoinRequestRevokedEvent array{
 *     id: string,
 *     type: 'group_join_request.revoked',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupJoinRequestRevokedData,
 * }
 * @phpstan-type GroupParticipantJoinedData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: string,
 *         name: string,
 *     },
 * }
 * @phpstan-type GroupParticipantJoinedEvent array{
 *     id: string,
 *     type: 'group_participant.joined',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupParticipantJoinedData,
 * }
 * @phpstan-type GroupParticipantLeftData array{
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     campaign: array{
 *         uuid: string,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: string,
 *         name: string,
 *     },
 * }
 * @phpstan-type GroupParticipantLeftEvent array{
 *     id: string,
 *     type: 'group_participant.left',
 *     version: int,
 *     occurred_at: string,
 *     data: GroupParticipantLeftData,
 * }
 * @phpstan-type MacExpiringData array{
 *     contacts_expiring: int,
 *     expires_at: string,
 * }
 * @phpstan-type MacExpiringEvent array{
 *     id: string,
 *     type: 'mac.expiring',
 *     version: int,
 *     occurred_at: string,
 *     data: MacExpiringData,
 * }
 * @phpstan-type MacUsage100Data array{
 *     used: int,
 *     limit: int,
 *     percentage: int,
 *     cycle_started_at: string,
 *     grace_expires_at: string,
 * }
 * @phpstan-type MacUsage100Event array{
 *     id: string,
 *     type: 'mac.usage_100',
 *     version: int,
 *     occurred_at: string,
 *     data: MacUsage100Data,
 * }
 * @phpstan-type MacUsage80Data array{
 *     used: int,
 *     limit: int,
 *     percentage: int,
 *     cycle_started_at: string,
 * }
 * @phpstan-type MacUsage80Event array{
 *     id: string,
 *     type: 'mac.usage_80',
 *     version: int,
 *     occurred_at: string,
 *     data: MacUsage80Data,
 * }
 * @phpstan-type MessageReceivedData array{
 *     message: array{
 *         id: int,
 *         type: 'text'|'image'|'video'|'document'|'sticker'|'contact'|'audio'|'note'|'action'|'invalid'|'interactive'|'location'|'voice_call'|'video_call'|'event'|'button'|'template'|'notification'|'option_list'|'template_button_reply'|'poll'|'whatsapp_form'|'call_permission_request'|'call_permission_reply',
 *         text: string,
 *         media: mixed,
 *     },
 *     contact: array{
 *         id: int,
 *         name: string,
 *         phone: string,
 *     },
 *     receiver: array{
 *         phone: string,
 *     },
 *     device_id?: int|null,
 * }
 * @phpstan-type MessageReceivedEvent array{
 *     id: string,
 *     type: 'message.received',
 *     version: int,
 *     occurred_at: string,
 *     data: MessageReceivedData,
 * }
 * @phpstan-type MessageStatusChangedData array{
 *     message_id: int,
 *     contact_id: int,
 *     device_id: int|null,
 *     status: 'sent'|'delivered'|'read'|'failed',
 *     previous_status: 'sent'|'delivered'|'read'|'failed'|null,
 *     error?: PendingMessageError,
 *     occurred_at: string,
 *     client_reference: string|null,
 * }
 * @phpstan-type MessageStatusChangedEvent array{
 *     id: string,
 *     type: 'message.status_changed',
 *     version: int,
 *     occurred_at: string,
 *     data: MessageStatusChangedData,
 * }
 * @phpstan-type PipelineFinishedData array{
 *     campaign: array{
 *         uuid: string,
 *     },
 *     pipeline: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     outcome: string,
 *     actions: array{
 *         total: int,
 *         succeeded: int,
 *         failed: int,
 *         skipped: int,
 *         cancelled: int,
 *     },
 * }
 * @phpstan-type PipelineFinishedEvent array{
 *     id: string,
 *     type: 'pipeline.finished',
 *     version: int,
 *     occurred_at: string,
 *     data: PipelineFinishedData,
 * }
 * @phpstan-type PollVotedData array{
 *     poll: array{
 *         uid: string,
 *     },
 *     group: array{
 *         uid: string,
 *     },
 *     scheduled_message: array{
 *         uuid: string,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: string,
 *         name: mixed,
 *     },
 *     options: list<string>,
 * }
 * @phpstan-type PollVotedEvent array{
 *     id: string,
 *     type: 'poll.voted',
 *     version: int,
 *     occurred_at: string,
 *     data: PollVotedData,
 * }
 * @phpstan-type ScheduledMessageFinishedData array{
 *     scheduled_message: array{
 *         uuid: string,
 *         name: string,
 *     },
 *     outcome: string,
 *     groups: array{
 *         total: int,
 *         failed: int,
 *         partially_failed: int,
 *     },
 *     occurrence: int,
 * }
 * @phpstan-type ScheduledMessageFinishedEvent array{
 *     id: string,
 *     type: 'scheduled_message.finished',
 *     version: int,
 *     occurred_at: string,
 *     data: ScheduledMessageFinishedData,
 * }
 * @phpstan-type ScheduledMessageGroupDeliveryFailedData array{
 *     scheduled_message: array{
 *         uuid: string,
 *     },
 *     group: array{
 *         uid: string,
 *         name: string,
 *     },
 *     reason: string,
 *     partial: bool,
 *     occurrence: int,
 * }
 * @phpstan-type ScheduledMessageGroupDeliveryFailedEvent array{
 *     id: string,
 *     type: 'scheduled_message.group_delivery_failed',
 *     version: int,
 *     occurred_at: string,
 *     data: ScheduledMessageGroupDeliveryFailedData,
 * }
 * @phpstan-type ScheduledMessageResponseReceivedData array{
 *     scheduled_message: array{
 *         uuid: string,
 *     },
 *     group: array{
 *         uid: string,
 *     },
 *     participant: array{
 *         identity: string,
 *         identity_type: string,
 *         name: string,
 *     },
 *     response: array{
 *         type: string,
 *         options: list<string>,
 *         rsvp: mixed,
 *     },
 * }
 * @phpstan-type ScheduledMessageResponseReceivedEvent array{
 *     id: string,
 *     type: 'scheduled_message.response_received',
 *     version: int,
 *     occurred_at: string,
 *     data: ScheduledMessageResponseReceivedData,
 * }
 * @phpstan-type SubscriptionCancellationConfirmedData array{
 *     access_until: string,
 * }
 * @phpstan-type SubscriptionCancellationConfirmedEvent array{
 *     id: string,
 *     type: 'subscription.cancellation_confirmed',
 *     version: int,
 *     occurred_at: string,
 *     data: SubscriptionCancellationConfirmedData,
 * }
 * @phpstan-type SubscriptionDormantEndingData array{
 *     charge_date: string,
 *     amount_cents: int,
 *     currency: string,
 * }
 * @phpstan-type SubscriptionDormantEndingEvent array{
 *     id: string,
 *     type: 'subscription.dormant_ending',
 *     version: int,
 *     occurred_at: string,
 *     data: SubscriptionDormantEndingData,
 * }
 * @phpstan-type SubscriptionExpiringData array<string, mixed>
 * @phpstan-type SubscriptionExpiringEvent array{
 *     id: string,
 *     type: 'subscription.expiring',
 *     version: int,
 *     occurred_at: string,
 *     data: SubscriptionExpiringData,
 * }
 * @phpstan-type SubscriptionReactivatedData array{
 *     renews_at: string,
 * }
 * @phpstan-type SubscriptionReactivatedEvent array{
 *     id: string,
 *     type: 'subscription.reactivated',
 *     version: int,
 *     occurred_at: string,
 *     data: SubscriptionReactivatedData,
 * }
 * @phpstan-type SubscriptionTrialEndingData array{
 *     trial_ends_at: string,
 * }
 * @phpstan-type SubscriptionTrialEndingEvent array{
 *     id: string,
 *     type: 'subscription.trial_ending',
 *     version: int,
 *     occurred_at: string,
 *     data: SubscriptionTrialEndingData,
 * }
 * @phpstan-type TemplateStatusChangedData array{
 *     template: array{
 *         id: int,
 *         uid: string,
 *         name: string,
 *         category: string,
 *     },
 *     previous_status: string,
 *     status: string,
 *     reason: mixed,
 * }
 * @phpstan-type TemplateStatusChangedEvent array{
 *     id: string,
 *     type: 'template.status_changed',
 *     version: int,
 *     occurred_at: string,
 *     data: TemplateStatusChangedData,
 * }
 * @phpstan-type WebhookTestData array{
 *     subscription: string,
 *     sent_at: string,
 * }
 * @phpstan-type WebhookTestEvent array{
 *     id: string,
 *     type: 'webhook.test',
 *     version: int,
 *     occurred_at: string,
 *     data: WebhookTestData,
 * }
 * @phpstan-type WhatsappFormCompletedData array{
 *     form: array{
 *         id: int,
 *         name: string,
 *         version_id: int,
 *     },
 *     message_id: int|null,
 *     contact_id: int|null,
 *     device_id: int|null,
 *     answers: list<array{
 *         key: string,
 *         label: string,
 *         value: string|int|float|bool|list<string>|null,
 *     }>,
 *     occurred_at: string,
 * }
 * @phpstan-type WhatsappFormCompletedEvent array{
 *     id: string,
 *     type: 'whatsapp_form.completed',
 *     version: int,
 *     occurred_at: string,
 *     data: WhatsappFormCompletedData,
 * }
 * @phpstan-type WebhookEventType 'account.blocked'|'ai_quota.low_balance'|'ai_quota.topup_failed'|'ai_quota.topup_succeeded'|'billing.payment_failed'|'billing.plan_blocked'|'billing.upcoming_charge'|'broadcast.finished'|'broadcast.paused'|'campaign.no_groups_available'|'campaign.status_changed'|'contact.created'|'contact.stage_changed'|'contact.tag_added'|'contact.tag_removed'|'conversation.assigned'|'conversation.closed'|'daily_report.generated'|'device.connected'|'device.deleted'|'device.disconnected'|'device.health_changed'|'flow.completed'|'flow.failed'|'group.blocked'|'group.created'|'group.full'|'group.invite_link_down'|'group.invite_link_renewed'|'group.message_received'|'group.reaction_added'|'group.recovered'|'group_action.failed'|'group_join_request.created'|'group_join_request.revoked'|'group_participant.joined'|'group_participant.left'|'mac.expiring'|'mac.usage_100'|'mac.usage_80'|'message.received'|'message.status_changed'|'pipeline.finished'|'poll.voted'|'scheduled_message.finished'|'scheduled_message.group_delivery_failed'|'scheduled_message.response_received'|'subscription.cancellation_confirmed'|'subscription.dormant_ending'|'subscription.expiring'|'subscription.reactivated'|'subscription.trial_ending'|'template.status_changed'|'webhook.test'|'whatsapp_form.completed'
 * @phpstan-type WebhookEvent array{
 *     id: string,
 *     type: string,
 *     version: int,
 *     occurred_at: string,
 *     data: mixed,
 * }
 */
final class WebhookEvents
{
    /**
     * Every webhook event type known to the spec (55). `status`: `live` is emitted today;
     * `planned` can be subscribed to, but nothing is emitted yet. `pending` = taken from
     * spec/pending.json; `pendingFields` = fields of `data` added by spec/pending.json.
     *
     * @var array<string, array{domains: list<string>, status: 'live'|'planned', pending: bool, pendingFields: list<string>, typedFrom: string, description: string|null}>
     */
    public const CATALOG = [
        'account.blocked' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The account was blocked for usage: the 24-hour grace period after using up its monthly active contacts ended.'],
        'ai_quota.low_balance' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'no example or schema yet', 'description' => 'The AI message quota is about to run out. Planned: not emitted yet.'],
        'ai_quota.topup_failed' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'no example or schema yet', 'description' => 'An AI quota top-up could not be processed. Planned: not emitted yet.'],
        'ai_quota.topup_succeeded' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'no example or schema yet', 'description' => 'An AI quota top-up was credited. Planned: not emitted yet.'],
        'billing.payment_failed' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'A subscription charge could not be processed (Stripe subscriptions only).'],
        'billing.plan_blocked' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'no example or schema yet', 'description' => 'The plan was blocked because of an unprocessed charge. Planned: not emitted yet.'],
        'billing.upcoming_charge' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The next subscription charge is approaching (Stripe subscriptions only).'],
        'broadcast.finished' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Una campaña llegó a su estado final (enviada, fallida o con fallos parciales), con sus conteos.\n\n`status` es `success`, `failed` o `partially_failed`. `reason` dice por qué terminó: `completed` (se enviaron todas las páginas), `cancelled` (se cerró una campaña pausada), `template_rejected` (Meta rechazó la plantilla, o su categoría cambió, mientras la campaña esperaba), `approval_expired` (la plantilla no se aprobó a tiempo), `mac_lockout` (la cuenta alcanzó su límite de contactos activos), `no_recipients` (la audiencia quedó vacía) o `error` (el envío no pudo despacharse). `totals` son los conteos del envío: `processed` (destinatarios procesados), `failed` y `skipped_unreachable` (destinatarios que el canal no puede alcanzar); valen 0 si la campaña terminó antes de enviar. Se emite una sola vez por campaña."],
        'broadcast.paused' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Una campaña se pausó sola, por ejemplo porque bajó la calidad del número.\n\n`reason` es `quality_degraded` (la calidad del número bajó respecto de la del inicio, o llegó a RED) o `quality_unreadable` (no se pudo leer la calidad en tres controles seguidos). `quality` y `quality_baseline` son la calidad de Meta leída en el control y la del inicio del envío (`GREEN`, `YELLOW`, `RED`), o `null` si no se pudo leer. Sólo las campañas con envío inteligente se pausan solas. Reanudar la campaña y que se pause otra vez emite otro evento."],
        'campaign.no_groups_available' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'A campaign has no groups to send the people who come in through its link to.'],
        'campaign.status_changed' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Una campaña se activó o se pausó.'],
        'contact.created' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Se creó un contacto nuevo en la cuenta.\n\nSe envía un evento por cada contacto nuevo, sea cual sea el canal: un mensaje entrante, la creación manual, una importación o la API. No se envía cuando el contacto ya existía y sólo se actualiza, ni para los contactos que la plataforma crea para sus propias notificaciones. En importaciones grandes el límite de volumen de la cuenta puede descartar parte de los eventos."],
        'contact.stage_changed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Un contacto cambió de etapa en un tablero.\n\nSe envía un evento por cada contacto que cambia de etapa en un tablero por etapas. `kind` es entry (entra al tablero, `from_stage` es null), move (pasa de una etapa a otra), exit (sale del tablero, `to_stage` es null) o stage_deleted (se reubica porque su etapa se borró; el nombre de la etapa borrada es null). No se envía si el contacto se coloca en la etapa en la que ya estaba."],
        'contact.tag_added' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Un contacto recibió una etiqueta.\n\nSe envía un evento por cada contacto y etiqueta agregados, también en operaciones en bloque; no se envía si el contacto ya tenía la etiqueta. `source` indica el origen: manual, flow, bulk, import, sync, walink o ai. En operaciones en bloque grandes el límite de volumen de la cuenta puede descartar parte de los eventos."],
        'contact.tag_removed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Un contacto perdió una etiqueta.\n\nSe envía un evento por cada contacto y etiqueta quitados, también en operaciones en bloque; no se envía si el contacto no tenía la etiqueta. No se envía al borrar la etiqueta, al borrar o fusionar contactos ni al desvincular etiquetas de un tablero. `source` indica el origen: manual, flow o bulk. En operaciones en bloque grandes el límite de volumen de la cuenta puede descartar parte de los eventos."],
        'conversation.assigned' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Una conversación quedó asignada a una persona del equipo o a un agente de IA.\n\nSe envía cuando una conversación queda asignada a una persona del equipo (`assignee.type` = `human`) o a un agente de IA (`assignee.type` = `ai`), por cualquier camino. `assignee` trae el tipo y el nombre, nunca un identificador. No se envía al desasignar, ni cuando se vuelve a enviar el mismo asignado."],
        'conversation.closed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Una conversación pasó a cerrada.\n\nSe envía cuando una conversación pasa de abierta a cerrada. No se envía si la conversación ya estaba cerrada ni cuando se reabre."],
        'daily_report.generated' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The account\'s daily report was generated (clicks, joins and exits).'],
        'device.connected' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => ['device.connection', 'device.external_reference', 'connect_link_id'], 'typedFrom' => 'schema', 'description' => 'One of the account\'s numbers connected: when the QR code was scanned or when a WhatsApp Cloud number finished onboarding, also from a connect link. `connection`, `external_reference` and `connect_link_id` (`null` when it did not come from a link) are from contract 1022-alta-de-numeros, not in production yet.'],
        'device.deleted' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'A number was deleted from the account (once per number, before the row is deleted). The deletion is physical and frees the quota; an official number loses the app subscription but is not deregistered from Meta; a QR number\'s session is closed. From contract 1022-alta-de-numeros: not emitted in production yet.'],
        'device.disconnected' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => ['device.connection', 'device.external_reference', 'reason'], 'typedFrom' => 'schema', 'description' => 'One of the account\'s numbers disconnected and can no longer send or receive: a QR session closed, or an official number left without registration or protected from a shadow ban. `reason`, `device.connection` and `device.external_reference` are from contract 1022-alta-de-numeros, not in production yet; so are the official numbers.'],
        'device.health_changed' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'The aggregated health of an official number changed (not on every Meta webhook). From contract 1022-alta-de-numeros: not emitted in production yet.'],
        'flow.completed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Un contacto terminó un flujo de automatización.\n\nSe envía una sola vez por cada ejecución de un flujo, cuando termina con éxito. No se envía si la ejecución se cortó, se canceló, quedó bloqueada o falló. `contact` es `null` si la ejecución no tiene un contacto asociado. Un flujo muy usado puede generar muchos eventos seguidos."],
        'flow.failed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "La ejecución de un flujo para un contacto terminó por un error.\n\nSe envía una sola vez por cada ejecución de un flujo que termina por un error, no porque llegó al final. `reason` indica el motivo y toma uno de estos valores: `node_missing` (el paso a ejecutar ya no existe), `unknown_node_type` (el paso es de un tipo no reconocido), `missing_subflow` (el flujo que debía ejecutarse dentro de otro ya no existe), `malformed_node` (el paso tiene una configuración dañada), `loop_detected` (la ejecución dio vueltas sin esperar nada) y `execution_error` (un error inesperado detuvo la ejecución). No se envía si la ejecución se canceló, quedó bloqueada o se cortó porque se editó el flujo o se unieron contactos. `contact` es `null` si la ejecución no tiene un contacto asociado."],
        'group.blocked' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Un grupo quedó inoperable: sin administrador conectado, con el link de invitación inválido o suspendido por WhatsApp.'],
        'group.created' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Se creó o se importó un grupo dentro de una campaña de la cuenta.'],
        'group.full' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'A group went from not full to full.'],
        'group.invite_link_down' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'El enlace de invitación de un grupo quedó confirmado caído (WhatsApp respondió que no existe y la renovación no consiguió otro); no se publica por errores transitorios ni por grupos prohibidos o suspendidos, y se publica una sola vez por episodio de bloqueo.'],
        'group.invite_link_renewed' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'El enlace de invitación de un grupo cambió por una renovación, automática al detectarse inválido o pedida por el usuario; en v0 no se publican los cambios que detecta la sincronización ni el enlace inicial al crear el grupo.'],
        'group.message_received' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'Someone wrote in a group of one of the account\'s numbers. It does not arrive for messages from your own numbers.'],
        'group.reaction_added' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'Someone reacted to a message in a group.'],
        'group.recovered' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Un grupo que estaba bloqueado volvió a operar.'],
        'group_action.failed' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'An automatic action on a group failed.'],
        'group_join_request.created' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'Someone asked to join an official group.'],
        'group_join_request.revoked' => ['domains' => ['communities'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'Someone withdrew their request to join an official group.'],
        'group_participant.joined' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Una persona entró a un grupo de una campaña.'],
        'group_participant.left' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Una persona salió de un grupo de una campaña.'],
        'mac.expiring' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Some monthly active contacts expire in the next 7 days.'],
        'mac.usage_100' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The account used up its monthly active contacts for the cycle; a 24-hour grace period starts.'],
        'mac.usage_80' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The account reached 80 % of its monthly active contacts for the cycle.'],
        'message.received' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => ['device_id'], 'typedFrom' => 'example + schema', 'description' => 'Un contacto le escribió a un número de WhatsApp de la cuenta.'],
        'message.status_changed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'The delivery status of a sent 1:1 message changed. One notice per transition, never one that goes backward; `queued` never arrives here. You must request it explicitly in `events` (`*` does not include it).'],
        'pipeline.finished' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Terminó un pipeline de operaciones sobre grupos, con su resultado agregado.'],
        'poll.voted' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Un participante votó en una encuesta enviada a un grupo, o cambió su voto.'],
        'scheduled_message.finished' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Un envío programado terminó, con su resultado agregado.'],
        'scheduled_message.group_delivery_failed' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Un envío programado falló en un grupo concreto, con el motivo desagregado.'],
        'scheduled_message.response_received' => ['domains' => ['communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Alguien respondió a un envío programado votando una encuesta o respondiendo a un evento.'],
        'subscription.cancellation_confirmed' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The subscription cancellation was confirmed (Stripe subscriptions only).'],
        'subscription.dormant_ending' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The dormant account period is about to end.'],
        'subscription.expiring' => ['domains' => ['accounts'], 'status' => 'planned', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'no example or schema yet', 'description' => 'The subscription is about to expire. Planned: not emitted yet.'],
        'subscription.reactivated' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The scheduled cancellation of the subscription was reverted (Stripe subscriptions only).'],
        'subscription.trial_ending' => ['domains' => ['accounts'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'The trial period is about to end.'],
        'template.status_changed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => "Una plantilla cambió de estado de aprobación en Meta.\n\n`template` identifica la plantilla igual que `GET /templates` (`id`, `uid`, `name`, `category`). `previous_status` y `status` son los estados tal como los devuelve la API (por ejemplo `PENDING`, `APPROVED`, `REJECTED`, `PAUSED`); `previous_status` es `null` si la plantilla no tenía estado. `reason` trae la observación de rechazo (traducida como en la API y limitada a 500 caracteres) cuando el cambio es un rechazo informado al crear la plantilla, y es `null` en el resto. No se emite si Meta reenvía el estado que la plantilla ya tenía. Si una misma plantilla de Meta figura en varias cuentas, cada cuenta recibe su propio evento."],
        'webhook.test' => ['domains' => ['conversations', 'communities'], 'status' => 'live', 'pending' => false, 'pendingFields' => [], 'typedFrom' => 'example', 'description' => 'Evento de prueba enviado desde la ruta de prueba de una suscripción.'],
        'whatsapp_form.completed' => ['domains' => ['conversations'], 'status' => 'live', 'pending' => true, 'pendingFields' => [], 'typedFrom' => 'schema', 'description' => 'A contact completed a WhatsApp form. It can arrive more than once: deduplicate by `id`.'],
    ];

    private function __construct() {}
}
