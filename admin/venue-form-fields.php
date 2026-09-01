<div class="form-group">
    <label for="name">Numele locației</label>
    <input id="name" name="name" type="text" maxlength="100" value="<?= e($venueInput['name']) ?>" required>
</div>
<div class="form-group">
    <label for="description">Descriere</label>
    <textarea id="description" name="description" maxlength="5000" rows="7" required><?= e($venueInput['description']) ?></textarea>
</div>
<div class="form-group">
    <label for="address">Adresă</label>
    <input id="address" name="address" type="text" maxlength="100" value="<?= e($venueInput['address']) ?>" required>
</div>
<div class="form-group">
    <label for="capacity">Capacitate</label>
    <input id="capacity" name="capacity" type="number" min="1" step="1" value="<?= e($venueInput['capacity']) ?>" required>
</div>
<div class="form-actions">
    <button class="button button-primary" type="submit"><?= e($submitLabel) ?></button>
    <a class="button button-secondary" href="venues.php">Anulează</a>
</div>
