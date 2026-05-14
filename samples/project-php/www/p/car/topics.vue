<template>
<div>
<table>
<thead><tr>
<th>ID</th>
<th>Trademark</th>
<th>Model</th>
<th>HP</th>
<th>Liter</th>
<th>Cyl</th>
<th>Category</th>
<th>Price</th>
</thead>
<tbody><tr v-for="item in names.data">
<td>
  <p-car-edit v-if="showModal && currentID===item.ID" v-bind:single="currentData" v-bind:id="currentID" @close="showModal=false">
  </p-car-edit>
  <button id="show-modal" @click="openModal(item.ID)">{{ item.ID }}</button>
</td>
<td>{{ item.Trademark }}</td>
<td>{{ item.Model }}</td>
<td>{{ item.HP }}</td>
<td>{{ item.Liter }}</td>
<td>{{ item.Cyl }}</td>
<td>{{ item.Category }}</td>
<td>{{ item.Price }}</td>
</tr>
</tbody>
</table>
</div>
</template>

<script>
module.exports = {
  name: 'p-car-topics',
  components: {
	'p-car-edit': httpVueLoader('./edit.vue'),
  },
  props: ['names'],
  data: function() {
    return {
		showModal: false,
        currentID: 0,
		currentData: null,
    };
  },
  methods: {
	openModal: function(id) {
      that = this;
      var mylanding = function(x) {
        that.currentData = JSON.parse(JSON.stringify(x.data[0]));
      };
      $scope.ajaxPage("p", "car", {action:"edit", ID:id}, "GET", mylanding);
      this.currentID = id;
      this.showModal = true;
    }
  }
}
</script>
