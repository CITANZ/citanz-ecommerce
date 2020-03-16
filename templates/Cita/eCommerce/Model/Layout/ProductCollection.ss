<div class="section">
    <div class="container">
        <h1 class="title is-1">$Title</h1>
        <div class="content">$Content</div>
        <div class="product-list" v-if="products">
            <div class="content">
                <ul>
                    <% loop $ProductList %>
                        <li>
                            <a href="$Link">$Title, $PriceLabel</a>
                        </li>
                    <% end_loop %>
                </ul>
            </div>
            <Pagination />
        </div>
        <% if $ProductList.MoreThanOnePage %>
        <nav class="pagination product-list__pagination" role="navigation" aria-label="pagination">
            <a href="$ProductList.PrevLink"<% if not $ProductList.NotFirstPage %> disabled<% end_if %> class="pagination-nav pagination-previous">Prev</a>
            <a href="$ProductList.NextLink"<% if not $ProductList.NotLastPage %> disabled<% end_if %> class="pagination-nav pagination-next">Next</a>
            <ul class="pagination-list">
                <% loop $ProductList.PaginationSummary %>
                    <li>
                    <% if $Link %>
                        <a class="pagination-link<% if $CurrentBool %> is-current<% end_if %>" href="$Link">$PageNum</a>
                    <% else %>
                        <span class="pagination-ellipsis">&hellip;</span>
                    <% end_if %>
                    </li>
                <% end_loop %>
            </ul>
        </nav>
        <% end_if %>
    </div>
</div>
