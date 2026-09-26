@php
    $tipoDocumento = old('tipo_documento', isset($clienteDocumento) && $clienteDocumento->nit ? 'nit' : 'dpi');
    $tipoDocumento = in_array($tipoDocumento, ['dpi', 'nit'], true) ? $tipoDocumento : 'dpi';
    $valorDocumento = old($tipoDocumento, isset($clienteDocumento) ? $clienteDocumento->{$tipoDocumento} : '');
    $valorDocumento = is_scalar($valorDocumento) ? $valorDocumento : '';
    $errorDocumento = $errors->first($tipoDocumento) ?: $errors->first('tipo_documento');
@endphp
<label for="documento">Documento de identificación <span class="text-danger">*</span></label>
<div class="input-group flex-nowrap">
    <label for="tipo_documento" class="visually-hidden">Tipo de documento</label>
    <select name="tipo_documento" id="tipo_documento" class="form-select selector-documento"
            data-tipo-documento aria-label="Tipo de documento">
        <option value="dpi" @selected($tipoDocumento === 'dpi')>DPI</option>
        <option value="nit" @selected($tipoDocumento === 'nit')>NIT</option>
    </select>
    <input type="text" name="{{ $tipoDocumento }}" id="documento" data-validacion="documento"
           data-tipo-original="{{ isset($clienteDocumento) && $clienteDocumento->nit ? 'nit' : 'dpi' }}"
           data-documento-original="{{ isset($clienteDocumento) ? ($clienteDocumento->nit ?? $clienteDocumento->dpi) : '' }}"
           class="form-control {{ $errorDocumento ? 'is-invalid' : '' }}"
           value="{{ $valorDocumento }}" required maxlength="{{ $tipoDocumento === 'dpi' ? 13 : 14 }}"
           inputmode="{{ $tipoDocumento === 'dpi' ? 'numeric' : 'text' }}"
           placeholder="{{ $tipoDocumento === 'dpi' ? '13 dígitos' : 'NIT, con o sin guion' }}"
           aria-describedby="documento-error documento-feedback"
           aria-invalid="{{ $errorDocumento ? 'true' : 'false' }}" autocomplete="off">
</div>
@include('partials.validacion-campo', ['campo' => 'documento', 'mensajeError' => $errorDocumento])
