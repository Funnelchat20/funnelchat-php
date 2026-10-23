<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Generated;

use Funnelchat\Resources\ConnectLinks;
use Funnelchat\Resources\Contacts;
use Funnelchat\Resources\Conversations;
use Funnelchat\Resources\CustomFields;
use Funnelchat\Resources\Devices;
use Funnelchat\Resources\Forms;
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
     * Messages to one contact (conversations area): send text, files and buttons, read the history
     * or one message by id, forward, react.
     */
    public readonly Messages $messages;

    /**
     * WhatsApp message templates (conversations area): list, create, edit, send.
     */
    public readonly Templates $templates;

    /**
     * WhatsApp Forms (conversations area): build them, version and publish them, choose their
     * numbers, and read the answers. To send one to a contact, `messages->sendForm()`.
     */
    public readonly Forms $forms;

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
     * WhatsApp groups (communities area), read only: the groups, their participants, joins and
     * leaves, messages, scheduled messages and actions.
     */
    public readonly Groups $groups;

    /**
     * WhatsApp numbers of the account (accounts area). The `id` of a number is the `device_id` used
     * to send.
     */
    public readonly Devices $devices;

    /**
     * Connect links (accounts area): a single-use page where your customer creates or reconnects a
     * WhatsApp number, by QR or with Meta's signup, without a Funnelchat session. The number is
     * created when the page ends. Learn the result by `device.connected` (with `connect_link_id`
     * and `external_reference`), by `return_url`, or with `retrieve()`.
     */
    public readonly ConnectLinks $connectLinks;

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
        $this->forms = new Forms($this);
        $this->tags = new Tags($this);
        $this->customFields = new CustomFields($this);
        $this->groups = new Groups($this);
        $this->devices = new Devices($this);
        $this->connectLinks = new ConnectLinks($this);
        $this->webhooks = new Webhooks($this);
    }
}
