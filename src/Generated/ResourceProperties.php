<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Generated;

use Funnelchat\Resources\Contacts;
use Funnelchat\Resources\Conversations;
use Funnelchat\Resources\CustomFields;
use Funnelchat\Resources\Devices;
use Funnelchat\Resources\Groups;
use Funnelchat\Resources\Messages;
use Funnelchat\Resources\Tags;
use Funnelchat\Resources\Templates;
use Funnelchat\Resources\Webhooks;

/**
 * The resources of the client, one per piece of the API (spec/resources.json).
 */
trait ResourceProperties
{
    /**
     * Contacts (conversations area): search, create, update, custom fields, notes and the 24-hour
     * window.
     */
    public readonly Contacts $contacts;

    /**
     * Conversations (conversations area): the inbox, its counters and the state of each
     * conversation.
     */
    public readonly Conversations $conversations;

    /**
     * Messages to one contact (conversations area): send text, files and buttons, read the history,
     * forward, react.
     */
    public readonly Messages $messages;

    /**
     * WhatsApp message templates (conversations area): list, create, edit, send.
     */
    public readonly Templates $templates;

    /**
     * Tags (conversations area).
     */
    public readonly Tags $tags;

    /**
     * Custom fields of contacts (conversations area). To set a value on a contact, see
     * `contacts->attachCustomField()`.
     */
    public readonly CustomFields $customFields;

    /**
     * WhatsApp groups (communities area). Today the public API reads groups; creating and managing
     * them by API (contract 1014, C3) is not in the spec yet.
     */
    public readonly Groups $groups;

    /**
     * WhatsApp numbers of the account (accounts area). The `id` of a number is the `device_id` used
     * to send.
     */
    public readonly Devices $devices;

    /**
     * Webhook subscriptions (accounts area) and the verification of deliveries.
     */
    public readonly Webhooks $webhooks;

    private function initResources(): void
    {
        $this->contacts = new Contacts($this);
        $this->conversations = new Conversations($this);
        $this->messages = new Messages($this);
        $this->templates = new Templates($this);
        $this->tags = new Tags($this);
        $this->customFields = new CustomFields($this);
        $this->groups = new Groups($this);
        $this->devices = new Devices($this);
        $this->webhooks = new Webhooks($this);
    }
}
