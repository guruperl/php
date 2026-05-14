var init_selection = function(arr, id) {
  var lists=[];
  arr.forEach((v,k)=>{
    if (parseInt(v.id)===id) lists.push(v.value_id);
  });
  return lists;
};

var init_selection_one = function(arr,id) {
  var lists={};
  arr.forEach((v,k)=>{
    if (parseInt(v.id)===id) lists=v;
  });
  return lists;
};

var init_selection_multiple = function(arr, ref) {
  var lists={id:[]};
  ref.forEach((v,k)=>{ lists[v.id] = []; });
  arr.forEach((v,k)=>{
    if (parseInt(v.id)>1000) {
      lists.id.push(v.id);
      lists[v.id].push(v.value_id);
    }
  });
  return lists;
};

var init_general = function(arr, attr) {
  var lists=[];
  arr.forEach((v,k)=>{ lists.push(v[attr]); });
  return lists;
};

var toggle_selection = function(lists, value_id) {
  if (lists===undefined) lists=[];
  var idx = lists.indexOf(value_id);
  if (idx>-1) lists.splice(idx,1);
  else lists.push(value_id);
};

var toggle_weight = function(lists, item, id_name) {
  var x=init_general(lists, id_name);
  var idx = x.indexOf(item[id_name]);
  if (idx>-1) lists.splice(idx,1);
  else lists.push(item);
};

var show_weight = function(lists, item, id_name) {
  var x=init_general(lists, id_name);
  var idx = x.indexOf(item[id_name]);
  return (idx>-1) ? false : true;
};
