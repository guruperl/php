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
<th></th>
</thead>
<tbody><tr v-for="item in names.data">
<td>
  <a-car-edit v-if="showModal && currentID===item.ID" v-bind:single="currentData" v-bind:id="currentID" @close="showModal=false">
  </a-car-edit>
  <button id="show-modal" @click="openModal(item.ID)">{{ item.ID }}</button>
</td>
<td>{{ item.Trademark }}</td>
<td>{{ item.Model }}</td>
<td>{{ item.HP }}</td>
<td>{{ item.Liter }}</td>
<td>{{ item.Cyl }}</td>
<td>{{ item.Category }}</td>
<td>{{ item.Price }}</td>
<td><button @click="deleteACar(item.ID)">DEL</button></td>
</tr>
</tbody>
</table>

<p>
<a-car-startnew v-if="newModal" @close="newModal=false">
</a-car-startnew>
<button id="new-modal" @click="newModal=true">添加</button>
</p>

</div>
</template>

<script>
module.exports = {
  name: 'a-car-topics',
  components: {
	'a-car-edit': httpVueLoader('./edit.vue'),
	'a-car-startnew': httpVueLoader('./startnew.vue'),
  },
  props: ['names'],
  data: function() {
    return {
		newModal: false,
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
      $scope.ajaxPage("a", "car", {action:"edit", ID:id}, "GET", mylanding);
      this.currentID = id;
      this.showModal = true;
    },
	deleteACar: function(id) {
      if (confirm("确认删除此ID: " + id + "?")) {
        $scope.ajaxPage("a", "car", {action:"delete", ID:id}, "GET", {operator:"delete", "id_name":"ID"});
      }
    }
  }
}
</script>
