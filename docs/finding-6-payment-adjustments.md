# Finding #6: payment adjustment holds

## Implemented policy

Payment verification and completed-order history remain unchanged. A separate adjustment ledger records provider snapshots, event history, purchase attribution, review decisions and reversible holds. License status, bound domains and manual revocation history are never changed by payment-hold restoration.

Automatic holds require a unique verified Paddle payment for the purchase, trusted customer identity established by a validated completed transaction, processed-event provenance, exact USD currency matching, and an approved full adjustment matching the original gross tax-inclusive payment total. This applies to full refunds, chargebacks and chargeback warnings. Partial/tax adjustments, unsupported or ambiguous totals, multiple verified payments, missing identity, conflicting attribution and unmatched events remain review-only. Pending/rejected refunds do not create a hold. Reversals never automatically restore access.

Admins use **Payment Adjustments** to inspect evidence, record a review note, suspend an eligible matched purchase, or restore a selected hold. Every decision requires a reason and is appended to history. A review note does not override later automatic processing; suspend/restore decisions do. An existing hold remains active across provider reversals or later ambiguous states until explicitly restored. Other active holds and manual license revocations continue blocking access.

## Locking and limitations

Adjustment and admin decision transactions acquire relevant orders first, then all payments for those purchases in ascending payment ID, then ledger rows. Provider transaction mapping is re-queried after locking. Verified-payment uniqueness uses current locked rows. Attribution-change retries begin only after the entire transaction has rolled back; retry exhaustion records webhook failure for later retry. Provider network calls are not used by these paths.

The provider version watermark retains microsecond precision and never decreases on conflict. Conflicts advance the admin event token and preserve evidence. History length also detects stale forms, including intervening admin decisions.

Downloads retain the existing release boundary and require an eligible unheld purchase. A different completed purchase with an independent non-revoked license can retain access. A held license cannot borrow another purchase's entitlement.

## Deployment and Paddle configuration

1. Back up and deploy through the normal release process. Apply the new additive migration **before enabling the new application code**. This implementation session does not migrate production.
2. Keep the existing transaction subscriptions and signature secret. Add **adjustment.created** and **adjustment.updated** to the same authenticated webhook destination. No Paddle settings or secrets were changed here.
3. Verify delivery and retry behavior in Paddle sandbox/staging, including tax-inclusive full refunds and chargeback-warning payloads. Confirm that the deployed API key/environment, endpoint and signing secret agree.
4. Verify PHP/runtime compatibility separately; this work was validated with PHP 8.5.10 and does not implement Finding #7.
5. Check the admin review queue and failure list. Reversal records identify the provider transaction; inspect all adjustment records for that transaction before selecting the original hold to restore.
6. For an ordinary failed/unprocessed event, resend the original signed event through Paddle. Event ID deduplication and adjustment ID uniqueness prevent duplicate effective processing. Do not synthesize provider events or weaken signature validation.

## Historical backfill and reconciliation

Inventory provider adjustments predating subscription/deployment using an authorized provider export outside application database transactions. Compare provider adjustment IDs with the local ledger and keep the original provider evidence.

Events already marked processed/ignored by the earlier webhook implementation remain deduplicated. Replaying those event IDs alone does not import them. Their backfill requires a separately reviewed, audited import/reconciliation procedure; do not delete event records or clear processing flags to bypass deduplication. Unseen historical signed adjustment events can enter the new ledger, but missing trusted payment customer identity still makes them review-only.

Likewise, ordinary replay of an already processed completed-payment event does not backfill customer identity. Historical identity must not be inferred from email, local customer IDs or browser input. Until provider evidence is safely reconciled through a reviewed procedure, leave those purchases in admin review rather than enabling automatic suspension.

Do not roll back the populated ledger table during a code rollback: retain the additive schema and adjustment/audit history. The migration has a conventional down() for standard tooling; this session never executes it against real data.

## Required prelaunch checks

- Exercise simultaneous distinct adjustments, completed-payment delivery, admin decisions, completion/recovery and license requests against real MySQL/InnoDB at the deployed isolation level. SQLite tests verify query ordering, current-row decisions and transaction rollback semantics, not actual MySQL lock contention.
- Confirm webhook retry alerting and admin responsibility for review-only cases and identity/backfill exceptions.
- Test the admin screens, reason validation and stale-form handling in the staging browser.
- Holds prevent subsequent downloads/validations; they cannot recall packages already downloaded or invalidate offline cached license results.
- Retain audit data and monitor ledger history growth.