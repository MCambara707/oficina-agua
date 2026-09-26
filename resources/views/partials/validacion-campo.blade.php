<span id="{{ $campo }}-error" data-error-campo="{{ $campo }}" class="visually-hidden">
    {{ $mensajeError ?? $errors->first($campo) }}
</span>
<span id="{{ $campo }}-feedback" data-feedback-campo="{{ $campo }}"
      class="visually-hidden"></span>
