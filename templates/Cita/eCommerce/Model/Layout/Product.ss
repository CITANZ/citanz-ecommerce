<div class="section">
    <div class="container">
        <h1 class="title is-1">$Title</h1>
        <div class="content">$Content</div>
        <% if $Variants %>
        <% loop $Variants %>
            <h2 class="title is-3">$Title</h2>
            $Top.VariantForm($ID)
        <% end_loop %>
        <% else %>
            $ProductForm
        <% end_if %>
    </div>
</div>
