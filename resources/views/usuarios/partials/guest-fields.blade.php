<label class="sre-field">
    <span class="sre-label">Nombres</span>
    <input class="sre-input" type="text" name="nombres" value="{{ old('nombres') }}" autocomplete="given-name" maxlength="32" pattern=".*\S.*" placeholder="Nombres" required>
    @error('nombres')
        <span class="sre-hint">{{ $message }}</span>
    @enderror
</label>
<label class="sre-field">
    <span class="sre-label">Apellidos</span>
    <input class="sre-input" type="text" name="apellidos" value="{{ old('apellidos') }}" autocomplete="family-name" maxlength="32" pattern=".*\S.*" placeholder="Apellidos" required>
    @error('apellidos')
        <span class="sre-hint">{{ $message }}</span>
    @enderror
</label>
<label class="sre-field">
    <span class="sre-label">Cédula</span>
    <input class="sre-input" type="text" name="cedula" value="{{ old('cedula') }}" inputmode="numeric" maxlength="24" pattern=".*\S.*" placeholder="Número de identificación" required>
    @error('cedula')
        <span class="sre-hint">{{ $message }}</span>
    @enderror
</label>
