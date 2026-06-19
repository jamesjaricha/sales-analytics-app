<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-brand-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:bg-brand-700 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 [transition:background-color_160ms_var(--ease-out),transform_160ms_var(--ease-out)]']) }}>
    {{ $slot }}
</button>
