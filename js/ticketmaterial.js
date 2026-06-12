(function() {
   function initPluginDropdowns(root) {
      if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
         return;
      }

      jQuery(root || document).find('select.plugin-maintenancecosts-dropdown').each(function() {
         var select = jQuery(this);
         if (select.data('maintenancecosts-ready')) {
            return;
         }
         select.data('maintenancecosts-ready', 1);
         var options = {
            width: '100%',
            allowClear: true,
            placeholder: '-----',
            minimumResultsForSearch: 0
         };
         var dropdownType = select.data('dropdown-type');
         if (dropdownType) {
            var rootDoc = (window.CFG_GLPI && CFG_GLPI.root_doc) ? CFG_GLPI.root_doc : '/glpi';
            options.minimumInputLength = dropdownType === 'contract' ? 0 : 1;
            options.ajax = {
               url: rootDoc + '/plugins/maintenancecosts/ajax/dropdown.php',
               dataType: 'json',
               delay: 250,
               data: function(params) {
                  return {
                     type: dropdownType,
                     q: params.term || '',
                     page: params.page || 1
                  };
               },
               processResults: function(data) {
                  return data || {results: []};
               },
               cache: true
            };
         }
         select.select2(options);
      });
   }

   function initSortableTables(root) {
      (root || document).querySelectorAll('table.plugin-maintenancecosts-sortable').forEach(function(table) {
         if (table.dataset.maintenancecostsSortableReady) {
            return;
         }
         table.dataset.maintenancecostsSortableReady = '1';
         table.querySelectorAll('thead th[data-sort]').forEach(function(header) {
            header.addEventListener('click', function() {
               var tbody = table.tBodies[0];
               if (!tbody) {
                  return;
               }
               var index = header.cellIndex;
               var direction = header.dataset.dir === 'asc' ? 'desc' : 'asc';
               table.querySelectorAll('thead th[data-sort]').forEach(function(th) {
                  delete th.dataset.dir;
               });
               header.dataset.dir = direction;

               var type = header.dataset.sort || 'text';
               var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function(row) {
                  return row.children.length > index && !row.dataset.noSort;
               });
               rows.sort(function(a, b) {
                  var av = (a.children[index].dataset.value || a.children[index].textContent || '').trim();
                  var bv = (b.children[index].dataset.value || b.children[index].textContent || '').trim();

                  if (type === 'number' || type === 'currency') {
                     av = toNumber(av);
                     bv = toNumber(bv);
                     return direction === 'asc' ? av - bv : bv - av;
                  }

                  return direction === 'asc'
                     ? av.localeCompare(bv, 'pt-BR', {numeric: true, sensitivity: 'base'})
                     : bv.localeCompare(av, 'pt-BR', {numeric: true, sensitivity: 'base'});
               });
               rows.forEach(function(row) {
                  tbody.appendChild(row);
               });
            });
         });
      });
   }

   function initColumnViews(root) {
      (root || document).querySelectorAll('table.plugin-maintenancecosts-table').forEach(function(table, index) {
         if (table.dataset.maintenancecostsColumnsReady) {
            return;
         }
         var headers = Array.prototype.slice.call(table.querySelectorAll('thead tr:last-child th'));
         if (headers.length < 2) {
            return;
         }

         table.dataset.maintenancecostsColumnsReady = '1';
         if (!table.dataset.tableId) {
            table.dataset.tableId = tableIdFor(table, index);
         }

         var controls = document.createElement('div');
         controls.className = 'plugin-maintenancecosts-view-controls';
         controls.innerHTML = ''
            + '<div class="plugin-maintenancecosts-view-title"><i class="ti ti-columns"></i> Visão da tabela</div>'
            + '<label><input type="radio" name="mc_view_' + table.dataset.tableId + '" value="personal" checked> Visão pessoal</label>'
            + '<label><input type="radio" name="mc_view_' + table.dataset.tableId + '" value="global"> Visão global</label>'
            + '<button type="button" class="btn btn-sm btn-secondary" data-maintenancecosts-reset-columns>Restaurar colunas</button>'
            + '<div class="plugin-maintenancecosts-column-list"></div>';

         var list = controls.querySelector('.plugin-maintenancecosts-column-list');
         headers.forEach(function(header, columnIndex) {
            var label = (header.textContent || '').replace(/[↕↑↓]/g, '').trim() || ('Coluna ' + (columnIndex + 1));
            var item = document.createElement('label');
            item.draggable = true;
            item.dataset.columnIndex = String(columnIndex);
            item.innerHTML = '<span class="plugin-maintenancecosts-drag-handle">↕</span> <input type="checkbox" value="' + columnIndex + '" checked> ' + escapeHtml(label);
            list.appendChild(item);
         });

         table.parentNode.insertBefore(controls, table);
         ensureColumnIndexes(table);
         applyColumnView(table, controls);

         controls.addEventListener('change', function(event) {
            if (event.target.matches('input[type="checkbox"], input[type="radio"]')) {
               persistColumnView(table, controls);
               applyColumnView(table, controls);
            }
         });

         controls.addEventListener('click', function(event) {
            if (!event.target.closest('[data-maintenancecosts-reset-columns]')) {
               return;
            }
            controls.querySelectorAll('.plugin-maintenancecosts-column-list input[type="checkbox"]').forEach(function(input) {
               input.checked = true;
            });
            restoreColumnOrder(table, controls);
            persistColumnView(table, controls);
            applyColumnView(table, controls);
         });

         controls.addEventListener('dragstart', function(event) {
            var item = event.target.closest('.plugin-maintenancecosts-column-list label');
            if (!item || !event.dataTransfer) {
               return;
            }
            event.dataTransfer.setData('text/plain', item.dataset.columnIndex || '');
            item.classList.add('dragging');
         });

         controls.addEventListener('dragend', function(event) {
            var item = event.target.closest('.plugin-maintenancecosts-column-list label');
            if (item) {
               item.classList.remove('dragging');
            }
         });

         controls.addEventListener('dragover', function(event) {
            if (event.target.closest('.plugin-maintenancecosts-column-list label')) {
               event.preventDefault();
            }
         });

         controls.addEventListener('drop', function(event) {
            var target = event.target.closest('.plugin-maintenancecosts-column-list label');
            var dragged = controls.querySelector('.plugin-maintenancecosts-column-list label.dragging');
            if (!target || !dragged || target === dragged) {
               return;
            }
            event.preventDefault();
            var rect = target.getBoundingClientRect();
            var before = event.clientY < rect.top + (rect.height / 2);
            target.parentNode.insertBefore(dragged, before ? target : target.nextSibling);
            persistColumnView(table, controls);
            applyColumnView(table, controls);
         });
      });
   }

   function tableIdFor(table, index) {
      var path = String(window.location.pathname || '').replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '');
      var title = (table.querySelector('thead th') && table.querySelector('thead th').textContent || '').replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '');
      return (path || 'maintenancecosts') + '_' + (title || 'table') + '_' + index;
   }

   function columnStorageKey(table, scope) {
      var userId = window.GLPI_USER_ID || document.body.dataset.userid || 'user';
      return 'maintenancecosts_columns_' + scope + '_' + (scope === 'personal' ? userId + '_' : '') + table.dataset.tableId;
   }

   function currentColumnScope(controls) {
      var checked = controls.querySelector('input[type="radio"]:checked');
      return checked ? checked.value : 'personal';
   }

   function loadColumnState(table, controls) {
      var scope = currentColumnScope(controls);
      var value = localStorage.getItem(columnStorageKey(table, scope));
      if (!value && scope === 'personal') {
         value = localStorage.getItem(columnStorageKey(table, 'global'));
      }
      if (!value) {
         return null;
      }
      try {
         var parsed = JSON.parse(value);
         if (Array.isArray(parsed)) {
            return {visible: parsed, order: []};
         }
         return parsed;
      } catch (e) {
         return null;
      }
   }

   function persistColumnView(table, controls) {
      var visible = Array.prototype.slice.call(controls.querySelectorAll('.plugin-maintenancecosts-column-list input[type="checkbox"]')).filter(function(input) {
         return input.checked;
      }).map(function(input) {
         return Number(input.value);
      });
      var order = Array.prototype.slice.call(controls.querySelectorAll('.plugin-maintenancecosts-column-list label')).map(function(item) {
         return Number(item.dataset.columnIndex);
      });
      localStorage.setItem(columnStorageKey(table, currentColumnScope(controls)), JSON.stringify({visible: visible, order: order}));
   }

   function applyColumnView(table, controls) {
      var state = loadColumnState(table, controls);
      var checkboxes = Array.prototype.slice.call(controls.querySelectorAll('.plugin-maintenancecosts-column-list input[type="checkbox"]'));
      if (state) {
         if (state.order && state.order.length) {
            reorderColumnControls(controls, state.order);
            reorderTableColumns(table, state.order);
            checkboxes = Array.prototype.slice.call(controls.querySelectorAll('.plugin-maintenancecosts-column-list input[type="checkbox"]'));
         }
         checkboxes.forEach(function(input) {
            input.checked = (state.visible || []).indexOf(Number(input.value)) !== -1;
         });
      }

      var visible = {};
      checkboxes.forEach(function(input) {
         visible[Number(input.value)] = input.checked;
      });
      table.querySelectorAll('tr').forEach(function(row) {
         Array.prototype.slice.call(row.children).forEach(function(cell) {
            var originalIndex = Number(cell.dataset.columnIndex);
            if (!isNaN(originalIndex)) {
               cell.style.display = visible[originalIndex] ? '' : 'none';
            }
         });
      });
   }

   function ensureColumnIndexes(table) {
      table.querySelectorAll('thead tr:last-child, tbody tr').forEach(function(row) {
         var cells = Array.prototype.slice.call(row.children);
         if (!cells.length || cells.some(function(cell) { return Number(cell.colSpan || 1) > 1; })) {
            return;
         }
         cells.forEach(function(cell, index) {
            if (!cell.dataset.columnIndex) {
               cell.dataset.columnIndex = String(index);
            }
         });
      });
   }

   function reorderColumnControls(controls, order) {
      var list = controls.querySelector('.plugin-maintenancecosts-column-list');
      if (!list) {
         return;
      }
      order.forEach(function(columnIndex) {
         var item = list.querySelector('label[data-column-index="' + columnIndex + '"]');
         if (item) {
            list.appendChild(item);
         }
      });
   }

   function reorderTableColumns(table, order) {
      table.querySelectorAll('thead tr:last-child, tbody tr').forEach(function(row) {
         var cells = Array.prototype.slice.call(row.children);
         if (!cells.length || cells.some(function(cell) { return Number(cell.colSpan || 1) > 1; })) {
            return;
         }
         order.forEach(function(columnIndex) {
            var cell = cells.find(function(candidate) {
               return Number(candidate.dataset.columnIndex) === Number(columnIndex);
            });
            if (cell) {
               row.appendChild(cell);
            }
         });
      });
   }

   function restoreColumnOrder(table, controls) {
      var headers = Array.prototype.slice.call(table.querySelectorAll('thead tr:last-child th'));
      var order = headers.map(function(header) { return Number(header.dataset.columnIndex); }).sort(function(a, b) { return a - b; });
      reorderColumnControls(controls, order);
      reorderTableColumns(table, order);
   }

   function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, function(char) {
         return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'})[char];
      });
   }

   function initContractTicketDropdowns(root) {
      if (!document.body || !document.body.innerText || document.body.innerText.indexOf('Adicionar um chamado') === -1) {
         return;
      }

      (root || document).querySelectorAll('select[name="tickets_id"]').forEach(function(select) {
         if (select.dataset.maintenancecostsContractReady) {
            return;
         }
         select.dataset.maintenancecostsContractReady = '1';

         fetchContractTickets()
            .then(function(items) {
               if (!items.length) {
                  return loadContractTicketsScript().then(function(scriptItems) {
                     return scriptItems;
                  });
               }
               return items;
            })
            .then(function(items) {
               select.innerHTML = '<option value="0">-----</option>';
               items.forEach(function(item) {
                  var option = document.createElement('option');
                  option.value = item.id;
                  option.textContent = item.text;
                  select.appendChild(option);
               });
               select.classList.add('plugin-maintenancecosts-dropdown');
               if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                  try {
                     jQuery(select).select2('destroy');
                  } catch (e) {}
               }
               initPluginDropdowns(select.parentNode || document);
            });
      });
   }

   function fetchContractTickets() {
      var root = (window.CFG_GLPI && CFG_GLPI.root_doc) ? CFG_GLPI.root_doc : '/glpi';
      return fetch(root + '/plugins/maintenancecosts/ajax/contracttickets.php', {credentials: 'same-origin'})
         .then(function(response) {
            if (!response.ok) {
               throw new Error('ajax endpoint unavailable');
            }
            return response.json();
         })
         .catch(function() {
            return fetch(root + '/plugins/maintenancecosts/front/contracttickets.php', {credentials: 'same-origin'})
               .then(function(response) { return response.ok ? response.json() : []; })
               .catch(function() { return []; });
         });
   }

   function loadContractTicketsScript() {
      return new Promise(function(resolve) {
         var root = (window.CFG_GLPI && CFG_GLPI.root_doc) ? CFG_GLPI.root_doc : '/glpi';
         var script = document.createElement('script');
         script.src = root + '/plugins/maintenancecosts/front/contracttickets.js.php?ts=' + Date.now();
         script.onload = function() {
            resolve(window.maintenanceCostsContractTickets || []);
         };
         script.onerror = function() {
            resolve([]);
         };
         document.head.appendChild(script);
      });
   }

   function toNumber(value) {
      value = String(value || '0').replace(/[^\d,.-]/g, '');
      if (value.indexOf(',') !== -1 && value.indexOf('.') !== -1) {
         value = value.replace(/\./g, '').replace(',', '.');
      } else {
         value = value.replace(',', '.');
      }

      return Number(value) || 0;
   }

   function formatDecimal(value) {
      return toNumber(value).toFixed(2).replace('.', ',');
   }

   function normalizeCompetence(value) {
      value = String(value || '').trim();
      if (!value) {
         return '';
      }

      var match = value.match(/^(\d{4})\D*(\d{1,2})/);
      if (!match) {
         var digits = value.replace(/\D/g, '');
         if (digits.length >= 6) {
            match = [null, digits.substring(0, 4), digits.substring(4, 6)];
         }
      }
      if (!match) {
         return value.substring(0, 7);
      }

      var month = Math.max(1, Math.min(12, parseInt(match[2], 10) || 1));
      return match[1] + '-' + String(month).padStart(2, '0');
   }

   function formatCurrency(value) {
      if (window.Intl && Intl.NumberFormat) {
         return new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'}).format(value);
      }

      return 'R$ ' + value.toFixed(2);
   }

   function updateTotal(form) {
      var quantity = toNumber(form.querySelector('[name="quantity"]') && form.querySelector('[name="quantity"]').value);
      var unitPrice = toNumber(form.querySelector('[name="unit_price_applied"]') && form.querySelector('[name="unit_price_applied"]').value);
      var total = form.querySelector('[data-maintenancecosts-total]');
      if (total) {
         total.textContent = formatCurrency(quantity * unitPrice);
      }
   }

   function loadMaterialInfo(form) {
      var material = form.querySelector('[name="plugin_maintenancecosts_materials_id"]');
      var competence = form.querySelector('[name="competence"]');
      var priceType = form.querySelector('[name="price_type"]');
      if (!material || !material.value) {
         updateTotal(form);
         return;
      }

      var url = CFG_GLPI.root_doc + '/plugins/maintenancecosts/ajax/materialinfo.php'
         + '?materials_id=' + encodeURIComponent(material.value)
         + '&competence=' + encodeURIComponent(competence ? competence.value : '')
         + '&price_type=' + encodeURIComponent(priceType ? priceType.value : 'sinapi');

      fetch(url)
         .then(function(response) { return response.ok ? response.json() : null; })
         .then(function(data) {
            if (!data) {
               return;
            }
            var unit = form.querySelector('[name="unit"]');
            var unitPrice = form.querySelector('[name="unit_price_applied"]');
            if (unit && data.unit) {
               unit.value = data.unit;
            }
            if (competence && data.competence) {
               competence.value = normalizeCompetence(data.competence);
            }
            if (unitPrice && data.has_price && data.unit_price !== null && !unitPrice.dataset.manualLocked) {
               unitPrice.value = formatDecimal(data.unit_price);
            }
            updateTotal(form);
         });
   }

   function normalizeFormFields(form) {
      var competence = form.querySelector('[name="competence"]');
      if (competence) {
         competence.value = normalizeCompetence(competence.value);
      }

      var quantity = form.querySelector('[name="quantity"]');
      if (quantity) {
         quantity.value = String(Math.max(0, Math.round(toNumber(quantity.value))));
      }

      ['unit_price_applied', 'unit_price'].forEach(function(name) {
         var input = form.querySelector('[name="' + name + '"]');
         if (input && input.value !== '') {
            input.value = formatDecimal(input.value);
         }
      });
   }

   function updateUnitPriceState(form) {
      var priceType = form.querySelector('[name="price_type"]');
      var unitPrice = form.querySelector('[name="unit_price_applied"]');
      if (!priceType || !unitPrice) {
         return;
      }

      if (priceType.value === 'cotacao_mercado') {
         unitPrice.readOnly = false;
      } else if (unitPrice.dataset.readonlyForSinapi === '1') {
         unitPrice.readOnly = true;
         unitPrice.dataset.manualLocked = '';
      }
   }

   document.addEventListener('change', function(event) {
      var form = event.target.closest('form');
      if (!form || !form.querySelector('[name="plugin_maintenancecosts_materials_id"]')) {
         return;
      }
      if (event.target.name === 'plugin_maintenancecosts_materials_id'
         || event.target.name === 'competence'
         || event.target.name === 'price_type') {
         if (event.target.name === 'competence') {
            event.target.value = normalizeCompetence(event.target.value);
         }
         if (event.target.name === 'price_type') {
            updateUnitPriceState(form);
         }
         loadMaterialInfo(form);
      }
      if (event.target.name === 'unit_price_applied') {
         event.target.value = formatDecimal(event.target.value);
         event.target.dataset.manualLocked = '1';
      }
      if (event.target.name === 'unit_price') {
         event.target.value = formatDecimal(event.target.value);
      }
      if (event.target.name === 'quantity') {
         event.target.value = String(Math.max(0, Math.round(toNumber(event.target.value))));
      }
      if (event.target.name === 'quantity' || event.target.name === 'unit_price_applied') {
         updateTotal(form);
      }
   });

   document.addEventListener('input', function(event) {
      var form = event.target.closest('form');
      if (form && form.querySelector('[name="plugin_maintenancecosts_materials_id"]') && (event.target.name === 'quantity' || event.target.name === 'unit_price_applied')) {
         updateTotal(form);
      }
   });

   document.addEventListener('blur', function(event) {
      if (event.target.name === 'competence') {
         event.target.value = normalizeCompetence(event.target.value);
      }
      if (event.target.name === 'quantity') {
         event.target.value = String(Math.max(0, Math.round(toNumber(event.target.value))));
      }
      if (event.target.name === 'unit_price_applied' || event.target.name === 'unit_price') {
         event.target.value = formatDecimal(event.target.value);
      }
   }, true);

   document.addEventListener('submit', function(event) {
      normalizeFormFields(event.target);
   });

   document.addEventListener('click', function(event) {
      var button = event.target.closest('[data-maintenancecosts-toggle-add]');
      if (!button) {
         return;
      }

      var container = button.parentNode.querySelector('[data-maintenancecosts-add-form]');
      if (!container) {
         return;
      }

      var isHidden = container.style.display === 'none' || container.style.display === '';
      container.style.display = isHidden ? 'block' : 'none';
      if (isHidden) {
         initPluginDropdowns(container);
      }
   });

   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
         initPluginDropdowns(document);
         initSortableTables(document);
         initColumnViews(document);
         initContractTicketDropdowns(document);
         document.querySelectorAll('form').forEach(updateUnitPriceState);
         observeDynamicContent();
      });
   } else {
      initPluginDropdowns(document);
      initSortableTables(document);
      initColumnViews(document);
      initContractTicketDropdowns(document);
      document.querySelectorAll('form').forEach(updateUnitPriceState);
      observeDynamicContent();
   }

   function observeDynamicContent() {
      if (window.maintenanceCostsObserverReady || !window.MutationObserver || !document.body) {
         return;
      }
      window.maintenanceCostsObserverReady = true;
      new MutationObserver(function(mutations) {
         mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
               if (!node || node.nodeType !== 1) {
                  return;
               }
               initPluginDropdowns(node);
               initSortableTables(node);
               initColumnViews(node);
               initContractTicketDropdowns(node);
            });
         });
      }).observe(document.body, {childList: true, subtree: true});
   }
})();
