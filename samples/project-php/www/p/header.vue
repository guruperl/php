<template>
<div>
<h5>header</h5>
<button id="a-top" @click="mygo('a','car','topics')">管理</button>
</div>
</template>

<script>
module.exports = {
  methods: {
    mygo: function(r, c, a) {
      $scope.go(r, c, a)
    }
  }
}
</script>
