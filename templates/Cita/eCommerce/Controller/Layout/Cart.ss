<div class="section">
    <div class="container">
        <h1 class="title is-1">$Title</h1>
        <% if $Cart && $Cart.Items.Count %>
            <% with $CartUpdateForm %>
            <form $AttributesHTML>
                <p class="message $MessageType">$Message</p>
                <div class="cart-content">
                    <table class="is-fullwidth table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <% loop $Top.Cart.Items %>
                            <tr>
                                <td>$Title</td>
                                <td style="width: 15%;">
                                    $Variant.Price.Nice
                                </td>
                                <td style="width: 15%;">
                                    <input type="hidden" name="ItemID[]" value="$ID" />
                                    <input name="Quantity[]" type="number" step="1" min="0" value="$Quantity" class="input" />
                                </td>
                                <td style="width: 10%;">
                                    $Subtotal.Nice
                                </td>
                                <td style="width: 10%;" class="has-text-right">
                                    <button type="submit" value="$ID" name="action_DeleteCartItem" class="button is-danger">Delete</button>
                                </td>
                            </tr>
                        <% end_loop %>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="has-text-right">
                                    <strong>Sub total</strong>: $Top.Cart.TotalAmount.Nice<br />
                                    <strong>GST</strong>: ${$Top.Cart.GST}<br />
                                    <strong>Grand total</strong>: $Top.Cart.PayableTotal.Nice
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <p class="has-text-right">
                        $Fields
                        $Actions
                    </p>
                </div>
            </form>
            <% end_with %>
        <% else %>
            <p>Your cart is empty.</p>
        <% end_if %>
    </div>
</div>
