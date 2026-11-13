<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary disabled:opacity-50']) }}>
    {{ $slot }}
</button>
