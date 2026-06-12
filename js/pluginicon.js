(function() {
   function applyMaintenanceCostsIcon() {
      var card = document.querySelector('li.plugin[data-key="maintenancecosts"]');
      if (!card) {
         return;
      }

      var iconSlot = card.querySelector('.main > .icon');
      if (!iconSlot || iconSlot.querySelector('img')) {
         return;
      }

      var img = document.createElement('img');
      img.src = (window.CFG_GLPI && window.CFG_GLPI.root_doc ? window.CFG_GLPI.root_doc : '') + '/plugins/maintenancecosts/pics/icon.png';
      img.alt = 'Custos de Manutenção';
      img.loading = 'lazy';
      iconSlot.textContent = '';
      iconSlot.appendChild(img);
   }

   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyMaintenanceCostsIcon);
   } else {
      applyMaintenanceCostsIcon();
   }

   document.addEventListener('glpi:page-loaded', applyMaintenanceCostsIcon);
})();
