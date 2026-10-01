<dialog class="sb-cancel-dialog" data-cancel-dialog aria-labelledby="cancel-dialog-title"
    aria-describedby="cancel-dialog-description">
    <form method="POST" data-cancel-form>
        @csrf
        @method('PATCH')
        <div class="sb-cancel-dialog-heading">
            <div>
                <span class="sb-kicker">RESERVATION</span>
                <h2 id="cancel-dialog-title">Cancel reservation?</h2>
            </div>
            <button class="sb-cancel-dialog-close" type="button" data-cancel-close aria-label="Close dialog">×</button>
        </div>
        <p id="cancel-dialog-description">Are you sure you want to cancel your reservation for <b data-cancel-gown></b>?
        </p>
        <div class="sb-cancellation-totals">
            <div><span>Down payment</span><b>₱<span data-cancel-payment>0.00</span></b></div>
            <div><span>Refund</span><b>₱0.00</b></div>
        </div>
        <p class="sb-cancellation-policy" data-cancel-policy>Your down payment is non-refundable after payment.</p>
        <div class="sb-cancel-dialog-actions">
            <button class="sb-outline-btn" type="button" data-cancel-close>Keep Reservation</button>
            <button class="sb-small-btn sb-reject-btn" type="submit">Cancel Reservation</button>
        </div>
    </form>
</dialog>

<script>
    (() => {
        const dialog = document.querySelector('[data-cancel-dialog]');
        if (!dialog) return;

        const form = dialog.querySelector('[data-cancel-form]');
        const gown = dialog.querySelector('[data-cancel-gown]');
        const payment = dialog.querySelector('[data-cancel-payment]');
        const policy = dialog.querySelector('[data-cancel-policy]');

        document.querySelectorAll('[data-cancel-open]').forEach(button => {
            button.addEventListener('click', () => {
                form.action = button.dataset.cancelAction;
                gown.textContent = button.dataset.cancelGown || 'this gown';
                const amount = Number(button.dataset.cancelPayment || 0);
                payment.textContent = amount.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                policy.textContent = amount > 0
                    ? 'Your down payment is non-refundable after payment. The verified amount will be retained; your refund is ₱0.00.'
                    : 'Down payments are non-refundable after payment. No verified down payment is currently recorded for this reservation.';
                dialog.showModal();
            });
        });

        dialog.querySelectorAll('[data-cancel-close]').forEach(button => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>