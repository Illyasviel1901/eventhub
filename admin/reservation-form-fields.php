<div class="form-group">
    <label for="user-id">Client</label>
    <select id="user-id" name="user_id" required>
        <option value="">Selectează clientul</option>
        <?php foreach ($users as $client): ?><option value="<?= (int) $client['id'] ?>"<?= $input['user_id'] === (string) $client['id'] ? ' selected' : '' ?>><?= e($client['name']) ?> — <?= e($client['email']) ?></option><?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label for="venue-id">Locație</label>
    <select id="venue-id" name="venue_id" required>
        <option value="">Selectează locația</option>
        <?php foreach ($venues as $venueOption): ?><option value="<?= (int) $venueOption['id'] ?>"<?= $input['venue_id'] === (string) $venueOption['id'] ? ' selected' : '' ?>><?= e($venueOption['name']) ?> — max. <?= (int) $venueOption['capacity'] ?> persoane</option><?php endforeach; ?>
    </select>
</div>
<div class="form-group"><label for="event-date">Data evenimentului</label><input id="event-date" name="event_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($input['event_date']) ?>" required></div>
<div class="form-group"><label for="event-name">Numele sau tipul evenimentului</label><input id="event-name" name="event_name" type="text" minlength="2" maxlength="100" value="<?= e($input['event_name']) ?>" required></div>
<div class="form-group"><label for="attendees">Număr de participanți</label><input id="attendees" name="attendees_count" type="number" min="1" step="1" value="<?= e($input['attendees_count']) ?>" required></div>
<div class="form-group"><label for="details">Detalii</label><textarea id="details" name="details" maxlength="5000" rows="7" required><?= e($input['details']) ?></textarea></div>
<div class="form-group">
    <label for="status">Status</label>
    <select id="status" name="status" required>
        <?php foreach (RESERVATION_STATUSES as $status): ?><option value="<?= e($status) ?>"<?= $input['status'] === $status ? ' selected' : '' ?>><?= e(reservationStatusLabel($status)) ?></option><?php endforeach; ?>
    </select>
</div>
<div class="form-actions"><button class="button button-primary" type="submit"><?= e($submitLabel) ?></button><a class="button button-secondary" href="reservations.php">Anulează</a></div>
