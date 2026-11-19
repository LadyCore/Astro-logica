<?php
/** @var array<string, mixed> $form */
/** @var string $mode carta-astral|revolucion-solar|transitos */
$mode = $mode ?? 'carta-astral';
$h = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="astro-form-grid">
  <label class="astro-field">
    <span>Nombre</span>
    <input type="text" name="name" value="<?= $h($form['name'] ?? '') ?>" required autocomplete="name">
  </label>

  <div class="astro-field-row">
    <label class="astro-field">
      <span>Año</span>
      <input type="number" name="year" min="1900" max="2100" value="<?= $h($form['year'] ?? '') ?>" required>
    </label>
    <label class="astro-field">
      <span>Mes</span>
      <input type="number" name="month" min="1" max="12" value="<?= $h($form['month'] ?? '') ?>" required>
    </label>
    <label class="astro-field">
      <span>Día</span>
      <input type="number" name="day" min="1" max="31" value="<?= $h($form['day'] ?? '') ?>" required>
    </label>
  </div>

  <div class="astro-field-row">
    <label class="astro-field">
      <span>Hora</span>
      <input type="number" name="hour" min="0" max="23" value="<?= $h($form['hour'] ?? '') ?>" required>
    </label>
    <label class="astro-field">
      <span>Minuto</span>
      <input type="number" name="minute" min="0" max="59" value="<?= $h($form['minute'] ?? '') ?>" required>
    </label>
    <label class="astro-field astro-field-check">
      <span>Hora conocida</span>
      <input type="hidden" name="time_known" value="0">
      <input type="checkbox" name="time_known" value="1" <?= !empty($form['time_known']) ? 'checked' : '' ?>>
    </label>
  </div>

  <label class="astro-field">
    <span>Ciudad de nacimiento</span>
    <input type="text" name="city" value="<?= $h($form['city'] ?? '') ?>" required>
  </label>

  <div class="astro-field-row">
    <label class="astro-field">
      <span>Latitud</span>
      <input type="number" step="any" name="lat" value="<?= $h($form['lat'] ?? '') ?>" required>
    </label>
    <label class="astro-field">
      <span>Longitud</span>
      <input type="number" step="any" name="lng" value="<?= $h($form['lng'] ?? '') ?>" required>
    </label>
  </div>

  <label class="astro-field">
    <span>Zona horaria</span>
    <input type="text" name="tz_str" value="<?= $h($form['tz_str'] ?? '') ?>" required>
  </label>

  <?php if ($mode === 'revolucion-solar'): ?>
    <div class="astro-field-row">
      <label class="astro-field">
        <span>Año de revolución</span>
        <input type="number" name="solar_year" min="1900" max="2100" value="<?= $h($form['solar_year'] ?? '') ?>" required>
      </label>
      <label class="astro-field">
        <span>Ciudad de retorno</span>
        <input type="text" name="current_city" value="<?= $h($form['current_city'] ?? $form['city'] ?? '') ?>" required>
      </label>
    </div>
  <?php endif; ?>

  <?php if ($mode === 'transitos'): ?>
    <label class="astro-field">
      <span>Momento del tránsito</span>
      <input type="datetime-local" name="transit_date" value="<?= $h(str_replace(' ', 'T', substr((string) ($form['transit_date'] ?? ''), 0, 16))) ?>" required>
    </label>
    <label class="astro-field">
      <span>Ciudad actual</span>
      <input type="text" name="current_city" value="<?= $h($form['current_city'] ?? '') ?>" required>
    </label>
    <div class="astro-field-row">
      <label class="astro-field">
        <span>Lat. actual</span>
        <input type="number" step="any" name="current_lat" value="<?= $h($form['current_lat'] ?? '') ?>">
      </label>
      <label class="astro-field">
        <span>Lng. actual</span>
        <input type="number" step="any" name="current_lng" value="<?= $h($form['current_lng'] ?? '') ?>">
      </label>
    </div>
  <?php endif; ?>
</div>
