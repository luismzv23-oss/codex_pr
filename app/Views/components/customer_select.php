<select id="<?= esc($customerSelectId ?? 'customer-select', 'attr') ?>" data-customer-search
    name="<?= !empty($selectedCustomer) ? 'customer_guid_locked' : 'customer_guid' ?>"
    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"
    <?= !empty($selectedCustomer) ? 'disabled' : '' ?> required>
    <option value="">Seleccionar cliente</option>
    <?php foreach ($customers as $customer): ?>
        <option value="<?= esc($customer['guid'], 'attr') ?>"
            data-customer-name="<?= esc($customer['full_name'], 'attr') ?>"
            data-customer-document="<?= esc($customer['dni'] ?? '', 'attr') ?>"
            <?= $lockedCustomerGuid === $customer['guid'] ? 'selected' : '' ?>><?= esc($customer['full_name']) ?> — <?= esc(!empty($customer['dni']) ? $customer['dni'] : 'Sin documento') ?></option>
    <?php endforeach; ?>
</select>
