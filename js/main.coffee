window.login=Backbone.Model.extend
  login:->
    info=
      'email':$('.email').val(),
      'password':$('.password').val()
    ApiUrl="./api/login/"
    $.ajax
      type: "POST",
      url: ApiUrl,
      data: info,
      dataType:"json"
      success:(msg)->
        if msg.success is true
          alert msg.text
          $('.login').css('-webkit-transform','translate3d(0, -200%, 0)')
          window.app.navigate("home", {trigger: true});
        else
          alert msg.text
window.TagItem=Backbone.Model.extend({})
window.TagCollection=Backbone.Collection.extend
  model:TagItem
  url:'./api/showtag/'
window.TagItemView=Backbone.View.extend
  tagName:'li'
  template:_.template('<a href="#tag/<%= name %>"><h3><%= name %>(<%= count %>)</h3></a>')
  render:->
    $(this.el).html(this.template(this.model.toJSON()))
    return this
window.TagListView=Backbone.View.extend
  tagName:'ul'
  render:->
    self=this
    _.each(this.model.models, (item)->
      $(this.el).append(new TagItemView({model:item}).render().el)
    ,this)
    return this    
window.NoteItem=Backbone.Model.extend({
  validate:(attrs)->
    if attrs.title is ""
      return "笔记标题不能为空"
    if attrs.content is ""
      return "笔记内容不能为空"
    if attrs.tag is ""
      return "标签内容不能为空"
})
window.NoteCollection=Backbone.Collection.extend
  model:NoteItem
  url:'./api/notes/'
window.NoteItemView=Backbone.View.extend
  tagName:'li'
  template:_.template('<a href="#notes/<%= id %>"><h3><%= title %></h3> <p><span class="time" ><%= date %></span><span class="tag"><%= tag %></span></p> </a>')
  events:
    "longTap li":"showMenu"
  showMenu:->
    $('#alertBox').show()
    nid=this.model.get('id');
    $('#opEdit').attr('nid',nid)
    $('#opDel').attr('nid',nid)
  initialize:->
    this.model.bind("destroy", this.close, this);
    this.model.bind("change", this.render, this);
  render:->
    $(this.el).html(this.template(this.model.toJSON()))
    return this
  close:->
    $(this.el).unbind();
    $(this.el).remove();

  #events:
    #"click .submit":"postComment"
window.isadd=false;
window.NoteListView=Backbone.View.extend
  tagName:'ul'
  initialize:->
    this.model.bind('reset', this.render, this)
    self = this;
    ###this.model.bind('add', (item)->#看单位了，ul的model是集合，添加一本书是在集合中
      $(this.el).prepend(new NoteItemView({model:item}).render().el)
      #这里用了self。el中是有对dom进行追踪的
    )###
    this.model.bind('add',(item)->
      #console.log(item)
      if window.isadd
        $(this.el).prepend(new NoteItemView({model:item}).render().el)
        window.isadd=false
      else
        $(this.el).append(new NoteItemView({model:item}).render().el)

      window.scrollNotes.refresh()
    ,this)
  render:->
    self=this
    _.each(this.model.models, (item)->
      $(this.el).append(new NoteItemView({model:item}).render().el)
    ,this)
    return this
window.NoteView=Backbone.View.extend
  template:_.template('<div class="title"><h3><%= title %></h3></div> <div class="meta">标签：<%= tag %> &nbsp;&nbsp; 时间：<%= date %></div> <div class="content"> <%= content %> </div>');
  
  render:->
    $(this.el).html(this.template(this.model.toJSON()))
    return this
  close:->
    $(this.el).unbind();
    $(this.el).empty();
  events:
    "click #edit_note":"saveNote"
  saveNote:->
    this.model.url='./api/editnote/';
    currentNote={
      title:$('#edit_title').val(),
      content:$('#edit_content').html(),
      tag:$('#edit_tag').val(),
    };
    this.model.save(currentNote,
      wait: true,
      success:(model, response)->
        alert "修改成功"
        window.app.navigate('home', true)
      error:(model, error)->
        alert error;
    )

window.AppRounter=Backbone.Router.extend
  routes:
    "home":"showDefault"
    "notes/:id":"showNote"
    "edit/:id":"editNote"
    "add":"addNote"
    "tag":"showTag"
    "tag/:name":"showListByTag"
  showListByTag:(name)->
    $('.tagnoteList').html("")
    window.tagNotes=new NoteCollection();
    getNote(window.tagNotes,window.tagNoteList,'./api/tag/'+name+'/',1,'.tagnoteList', '#tagNote')
    #show('#tagNote','#notes')
    show('#tagNote','#showTag')
    $('#showNote').css('-webkit-transform','translate3d(-100%, 0, 0)')
    setTimeout(->
      $('#showNote').hide()
    ,500)
    window.scrollTagNote.refresh()
  showTag:->
    if $('.tagList li').length < 1
      show('#showTag','#notes')
      window.tags=new TagCollection();
      window.tags.fetch
        success:->
          window.tagListView=new TagListView({model:window.tags});
          $('.tagList').append(window.tagListView.render().el)
    else
      show('#showTag','#notes')
      show('#showTag','#tagNote')

    window.scrollShowTag.refresh()



  addNote:->
    show('#addNote','#notes')
    window.scrollAddNote.refresh()

  editNote:(id)->
    show('#editNote','#notes')
    this.note = window.homeNotes.get(id);
    if  app.noteView then app.noteView.close();
    this.noteView = new NoteView({model: this.note});
    this.noteView.template=_.template('<input type="text" class="txtBlock" id="edit_title" placeholder="笔记标题，选填" value="<%= title %>"> <div class="editor" contenteditable="true" id="edit_content" ><%= content %></div><input type="file" class="pickImg" /><input type="text" class="txtBlock" value="<%= tag %>" id="edit_tag" placeholder="标签，用逗号分隔"> <input type="submit" class="submit s_button" id="edit_note" value="保存更改">');
    $('#editNote .box').html(this.noteView.render().el);
    window.scrollEditNote.refresh()
  showDefault:->
    if $('.noteList li').length < 1
      window.homeNotes=new NoteCollection();
      getNote(window.homeNotes,window.noteListView,'./api/notes/',1,'.noteList', '#notes')
    else
      $('#notes').show()
      $('#addNote').css('-webkit-transform','translate3d(100%, 0, 0)')
      $('#showNote').css('-webkit-transform','translate3d(100%, 0, 0)')
      $('#editNote').css('-webkit-transform','translate3d(100%, 0, 0)')
      $('#showTag').css('-webkit-transform','translate3d(100%, 0, 0)')
      $('#notes').css('-webkit-transform','translate3d(0, 0, 0)')
      setTimeout(->
        $('#addNote').hide()
        $('#showNote').hide()
        $('#editNote').hide()
        $('#showTag').hide()
      ,500)

    window.scrollNotes.refresh()
  showNote:(id)->
    show('#showNote','#notes')
    show('#showNote','#tagNote')
    this.note = window.homeNotes.get(id)||window.tagNotes.get(id);
    if  app.noteView then app.noteView.close();
    this.noteView = new NoteView({model: this.note});
    $('.detail').html(this.noteView.render().el);
    window.scrollShowNote.refresh()
show=(self, main)->
    $(self).css('-webkit-transform','translate3d( 100%, 0, 0)')
    $(self).show()
    $(main).css('-webkit-transform','translate3d(-100%, 0, 0)')
    $(self).css('-webkit-transform','translate3d(0, 0, 0)')
    setTimeout(->
      $(main).hide()
    ,500)

getNote=(notesCollection, globalNoteList,apiurl,page, node, panel)->
  notesCollection.url=apiurl+page;
  notesCollection.fetch
    add: true,
    success:->
      if !globalNoteList
        globalNoteList=new NoteListView({model:notesCollection});
      if page<2
        $(node).append(globalNoteList.render().el)
        $(node).append('<div class="more" url="'+apiurl+'" page="'+page+'">加载更多</div><div class="loading">正在加载中……</div>')
      $('.loading').hide();
      $('.more').show()   
      $(panel).show()
      localStorage.setItem('homeNotes', JSON.stringify(window.homeNotes));
      
 
      


$ ->
  $('.btnlogin').click(->
    login=new window.login()
    login.login();
  )
  $('.more').live('click',->
    page=Number($(this).attr('page'))+1;
    $('.loading').show();
    $(this).hide()
    getNote(window.homeNotes,window.noteListView,'./api/notes/',page, '.noteList', '#notes')
    $(this).attr('page',page);

  )
   
  $('#create_note').click(->
    noteData={
      title:$('#note_title').val(),
      content:$('#note_content').html(),
      tag:$('#note_tag').val()
    }
    noteModel=new NoteItem(noteData)
    window.homeNotes.url='./api/newnote/'
    window.isadd=true
    window.homeNotes.create(noteModel,
      wait: true,
      success:(model, response)->

        alert "添加成功"
        window.app.navigate('home/', true)
        #console.log(model)
        #window.app.navigate('notes/'+model.id, false)
      error:(model, error)->
        alert error;
        window.isadd=false
    )
  )
  $('#opEdit').click(->
    $('#alertBox').hide();

    id=$(this).attr('nid');
    window.app.navigate('edit/'+id, true)
  )
  $('#searchTag').click(->
    tag=$('#tagbox').val()
    window.app.navigate('tag/'+tag, true)
  )
  $('#opDel').click(->
    $('#alertBox').hide();
    if confirm("您确定要删除该笔记?")
      id=$(this).attr('nid');
      delnote = window.homeNotes.get(id);
      delnote.urlRoot='./api/delnote/'
      delnote.destroy(
        success:->
          alert('该笔记已经成功删除');
        error:->
          alert('删除失败');
      )
  )
  $(".pickImg").live("change",->
    readFileAsDataURL(this.files[0]);
  )
  readFileAsDataURL=(file)->
    reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload =(event)->
      img = $('<img  >');
      img.attr('src', event.target.result);
      img.appendTo('.editor');
    reader.onerror =->
      alert "读取该文件失败"
  window.scrollNotes = new iScroll('notes');
  window.scrollAddNote = new iScroll('addNote',{onBeforeScrollStart:inputHack});
  window.scrollShowNote = new iScroll('showNote');
  window.scrollEditNote = new iScroll('editNote',{onBeforeScrollStart:inputHack});
  window.scrollTagNote = new iScroll('tagNote');
  window.scrollShowTag = new iScroll('showTag',{onBeforeScrollStart:inputHack});
  inputHack=(e)->
    target = e.target;
    while  target.nodeType != 1
      target = target.parentNode;
    if target.tagName != 'SELECT' && target.tagName != 'INPUT' && target.tagName != 'TEXTAREA'
      e.preventDefault();


   
  window.app=new AppRounter()
  Backbone.history.start()
