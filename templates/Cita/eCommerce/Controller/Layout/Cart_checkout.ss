<div class="section">
    <div class="container">
        <h1 class="title is-1">$Title</h1>
        <% if $Cart && $Cart.Items.Count %>
            $CheckoutForm
        <% else %>
            <p>There is nothing to check out with.</p>
        <% end_if %>
    </div>
</div>
