<template>
  <transition name="modal">
    <div class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">

          <div class="modal-header">
              <button class="modal-default-button" @click="$emit('close')">x
              </button>
            <slot name="header"></slot>
          </div>

          <div class="modal-body">
            <slot name="body"></slot>
       		<form id="a_car_insert" @submit.prevent="sendit">
<pre>
Trademark: <INPUT TYPE="TEXT" v-model="f0.Trademark">
    Model: <INPUT TYPE="TEXT" v-model="f0.Model">
       HP: <INPUT TYPE="TEXT" v-model="f0.HP">
    Liter: <INPUT TYPE="TEXT" v-model="f0.Liter">
      Cyl: <INPUT TYPE="TEXT" v-model="f0.Cyl">
 Category: <INPUT TYPE="TEXT" v-model="f0.Category">
    Price: <INPUT TYPE="TEXT" v-model="f0.Price">
</pre>
<button TYPE="SUBMIT"> 添加 </button>
</form> 
          </div>

          <div class="modal-footer">
            <slot name="footer">
              <button class="modal-default-button" @click="$emit('close')"> 关闭
              </button>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
  module.exports = {
    name: 'a-car-startnew',
	data: function() {
		return { f0 : {} }
	},
	methods: {
      sendit: function() {
        $scope.send('a', 'car', 'insert', this.f0, {operator:"insert"});
		this.$emit('close');
      }
	}
  }
</script>

<style>
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, .5);
  display: table;
  transition: opacity .3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 600px;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
  transition: all .3s ease;
  font-family: Helvetica, Arial, sans-serif;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 20px 0;
}

.modal-default-button {
  float: right;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}
</style>
