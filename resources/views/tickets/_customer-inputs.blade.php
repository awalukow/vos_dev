<script>
document.querySelectorAll('input[name="dob"]').forEach(input => {
    input.addEventListener('input', () => {
        const digits = input.value.replace(/[^0-9]/g, '').slice(0, 8);
        const formatted = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4)].filter(Boolean).join('/');
        if (input.value !== formatted) input.value = formatted;
    });
});
document.querySelectorAll('input[name="phone"]').forEach(input => {
    const normalize = () => {
        const digits = input.value.replace(/[^0-9]/g, '');
        if (digits) input.value = digits.startsWith('62') ? digits : '62' + digits.replace(/^0+/, '');
    };
    input.addEventListener('blur', normalize);
    input.form.addEventListener('submit', normalize);
});
</script>
