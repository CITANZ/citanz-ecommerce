(function($)
{
    $.entwine('ss', function($)
    {
        $('.btn-send-invoice, .btn-refund, .btn-send-tracking, .btn-cheque-cleared').entwine(
        {
            onclick: function()
            {
                var error_handler   =   function(event, jqxhr, settings, thrownError)
                                        {
                                            $(document).unbind('ajaxError', error_handler);
                                            window.location.reload();
                                        },
                    success_handler =   function( event, request, settings )
                                        {
                                            $(document).unbind('ajaxSuccess', success_handler);
                                            console.log("reload");
                                            window.location.reload();
                                        };

                $(document).ajaxError(error_handler).ajaxSuccess(success_handler);
                this._super();
            }
        });

        $('#Form_EditForm .search-form__wrapper').entwine({
            onmatch: function() {
                isReady(document.querySelector("body"), () => {
                    $('#Form_EditForm .search-form__wrapper .form-group.checkbox .form__field-holder').each(function(i, el) {
                        $(this).addClass('ml-0')
                    })
                })
            }
        });

        $('#Form_EditForm').entwine({
          onmatch: function() {
            window.$ = $
            const targetFieldset = $('#Form_EditForm fieldset.grid.grid-field');
            const service = targetFieldset.data('name')

            if (localStorage.lastService != service) {
              delete localStorage.lastSchema
              delete localStorage.lastGridState
            }

            localStorage.setItem('lastService', service)

            if (localStorage.lastSchema) {
              const schema = JSON.parse(localStorage.lastSchema)
              const gridState = localStorage.lastGridState ? localStorage.lastGridState : '{}'
              if (Object.keys(schema.filters).length) {
                const url = targetFieldset.data('url')
                const security_id = $('#Form_EditForm input[name="SecurityID"]').val()
                const data = {}

                data[`${schema.searchAction}`] = ''

                for (let key in schema.filters) {
                  const datakey = `filter[${service}][${key.replace('Search__', '')}]`
                  data[datakey] = schema.filters[key]
                }

                data[`${service}[GridState]`] = gridState
                data['SecurityID'] = security_id

                $.ajax({
                  url: url,
                  type: 'post',
                  data: data,
                  headers: {
                    'X-Pjax': 'CurrentField'
                  }
                }).done(resp => {
                  targetFieldset.replaceWith(resp)
                  $('[name="showFilter"]').click()
                })
              }
            }

            isReady(document.querySelector("body"), (schema, gridState) => {
              localStorage.setItem('lastSchema', JSON.stringify(schema))
              localStorage.setItem('lastGridState', gridState)
            })
          },
        })
    });
}(jQuery));

function isReady(targetNode, cbf) {
  const config = { childList: true, subtree: true, attributes: true };

  const callback = function(mutationsList, observer) {
    for(const mutation of mutationsList) {
      if (mutation.type === 'attributes') {
        if ($(mutation.target).is('.search-holder')) {
          const service = $('#Form_EditForm fieldset.grid.grid-field').data('name')
          const schema = $(mutation.target).data('schema');
          if (schema.gridfield == localStorage.lastService) {
            const gridState = $(`#Form_EditForm input[name="${service}[GridState]"]`).val()
            cbf(schema, gridState)
          }
        }
      }
    }
  };

  const observer = new MutationObserver(callback);
  observer.observe(targetNode, config);
}
