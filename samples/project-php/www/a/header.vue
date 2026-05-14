<template>
<div>
<h5>header
<button @click="logout('a', 'logout', {role:'p', comp:'car', action:'topics'})">Logout</button>
</h5>
</div>
</template>

<script>
module.exports = {
  methods: {
    logout: function(r, c, landing) {
      $scope.ajaxPage(r, c, null, 'GET', landing)
    }
  }
}
</script>
