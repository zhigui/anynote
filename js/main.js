// Generated by CoffeeScript 1.3.3
(function() {
  var getNote, show;

  window.login = Backbone.Model.extend({
    login: function() {
      var ApiUrl, info;
      info = {
        'email': $('.email').val(),
        'password': $('.password').val()
      };
      ApiUrl = "./api/login/";
      return $.ajax({
        type: "POST",
        url: ApiUrl,
        data: info,
        dataType: "json",
        success: function(msg) {
          if (msg.success === true) {
            alert(msg.text);
            $('.login').css('-webkit-transform', 'translate3d(0, -200%, 0)');
            return window.app.navigate("home", {
              trigger: true
            });
          } else {
            return alert(msg.text);
          }
        }
      });
    }
  });

  window.TagItem = Backbone.Model.extend({});

  window.TagCollection = Backbone.Collection.extend({
    model: TagItem,
    url: './api/showtag/'
  });

  window.TagItemView = Backbone.View.extend({
    tagName: 'li',
    template: _.template('<a href="#tag/<%= name %>"><h3><%= name %>(<%= count %>)</h3></a>'),
    render: function() {
      $(this.el).html(this.template(this.model.toJSON()));
      return this;
    }
  });

  window.TagListView = Backbone.View.extend({
    tagName: 'ul',
    render: function() {
      var self;
      self = this;
      _.each(this.model.models, function(item) {
        return $(this.el).append(new TagItemView({
          model: item
        }).render().el);
      }, this);
      return this;
    }
  });

  window.NoteItem = Backbone.Model.extend({
    validate: function(attrs) {
      if (attrs.title === "") {
        return "笔记标题不能为空";
      }
      if (attrs.content === "") {
        return "笔记内容不能为空";
      }
      if (attrs.tag === "") {
        return "标签内容不能为空";
      }
    }
  });

  window.NoteCollection = Backbone.Collection.extend({
    model: NoteItem,
    url: './api/notes/'
  });

  window.NoteItemView = Backbone.View.extend({
    tagName: 'li',
    template: _.template('<a href="#notes/<%= id %>"><h3><%= title %></h3> <p><span class="time" ><%= date %></span><span class="tag"><%= tag %></span></p> </a>'),
    events: {
      "longTap li": "showMenu"
    },
    showMenu: function() {
      var nid;
      $('#alertBox').show();
      nid = this.model.get('id');
      $('#opEdit').attr('nid', nid);
      return $('#opDel').attr('nid', nid);
    },
    initialize: function() {
      this.model.bind("destroy", this.close, this);
      return this.model.bind("change", this.render, this);
    },
    render: function() {
      $(this.el).html(this.template(this.model.toJSON()));
      return this;
    },
    close: function() {
      $(this.el).unbind();
      return $(this.el).remove();
    }
  });

  window.isadd = false;

  window.NoteListView = Backbone.View.extend({
    tagName: 'ul',
    initialize: function() {
      var self;
      this.model.bind('reset', this.render, this);
      self = this;
      /*this.model.bind('add', (item)->#看单位了，ul的model是集合，添加一本书是在集合中
        $(this.el).prepend(new NoteItemView({model:item}).render().el)
        #这里用了self。el中是有对dom进行追踪的
      )
      */

      return this.model.bind('add', function(item) {
        if (window.isadd) {
          $(this.el).prepend(new NoteItemView({
            model: item
          }).render().el);
          window.isadd = false;
        } else {
          $(this.el).append(new NoteItemView({
            model: item
          }).render().el);
        }
        return window.scrollNotes.refresh();
      }, this);
    },
    render: function() {
      var self;
      self = this;
      _.each(this.model.models, function(item) {
        return $(this.el).append(new NoteItemView({
          model: item
        }).render().el);
      }, this);
      return this;
    }
  });

  window.NoteView = Backbone.View.extend({
    template: _.template('<div class="title"><h3><%= title %></h3></div> <div class="meta">标签：<%= tag %> &nbsp;&nbsp; 时间：<%= date %></div> <div class="content"> <%= content %> </div>'),
    render: function() {
      $(this.el).html(this.template(this.model.toJSON()));
      return this;
    },
    close: function() {
      $(this.el).unbind();
      return $(this.el).empty();
    },
    events: {
      "click #edit_note": "saveNote"
    },
    saveNote: function() {
      var currentNote;
      this.model.url = './api/editnote/';
      currentNote = {
        title: $('#edit_title').val(),
        content: $('#edit_content').html(),
        tag: $('#edit_tag').val()
      };
      return this.model.save(currentNote, {
        wait: true,
        success: function(model, response) {
          alert("修改成功");
          return window.app.navigate('home', true);
        },
        error: function(model, error) {
          return alert(error);
        }
      });
    }
  });

  window.AppRounter = Backbone.Router.extend({
    routes: {
      "home": "showDefault",
      "notes/:id": "showNote",
      "edit/:id": "editNote",
      "add": "addNote",
      "tag": "showTag",
      "tag/:name": "showListByTag"
    },
    showListByTag: function(name) {
      $('.tagnoteList').html("");
      window.tagNotes = new NoteCollection();
      getNote(window.tagNotes, window.tagNoteList, './api/tag/' + name + '/', 1, '.tagnoteList', '#tagNote');
      show('#tagNote', '#showTag');
      $('#showNote').css('-webkit-transform', 'translate3d(-100%, 0, 0)');
      setTimeout(function() {
        return $('#showNote').hide();
      }, 500);
      return window.scrollTagNote.refresh();
    },
    showTag: function() {
      if ($('.tagList li').length < 1) {
        show('#showTag', '#notes');
        window.tags = new TagCollection();
        window.tags.fetch({
          success: function() {
            window.tagListView = new TagListView({
              model: window.tags
            });
            return $('.tagList').append(window.tagListView.render().el);
          }
        });
      } else {
        show('#showTag', '#notes');
        show('#showTag', '#tagNote');
      }
      return window.scrollShowTag.refresh();
    },
    addNote: function() {
      show('#addNote', '#notes');
      return window.scrollAddNote.refresh();
    },
    editNote: function(id) {
      show('#editNote', '#notes');
      this.note = window.homeNotes.get(id);
      if (app.noteView) {
        app.noteView.close();
      }
      this.noteView = new NoteView({
        model: this.note
      });
      this.noteView.template = _.template('<input type="text" class="txtBlock" id="edit_title" placeholder="笔记标题，选填" value="<%= title %>"> <div class="editor" contenteditable="true" id="edit_content" ><%= content %></div><input type="file" class="pickImg" /><input type="text" class="txtBlock" value="<%= tag %>" id="edit_tag" placeholder="标签，用逗号分隔"> <input type="submit" class="submit s_button" id="edit_note" value="保存更改">');
      $('#editNote .box').html(this.noteView.render().el);
      return window.scrollEditNote.refresh();
    },
    showDefault: function() {
      if ($('.noteList li').length < 1) {
        window.homeNotes = new NoteCollection();
        getNote(window.homeNotes, window.noteListView, './api/notes/', 1, '.noteList', '#notes');
      } else {
        $('#notes').show();
        $('#addNote').css('-webkit-transform', 'translate3d(100%, 0, 0)');
        $('#showNote').css('-webkit-transform', 'translate3d(100%, 0, 0)');
        $('#editNote').css('-webkit-transform', 'translate3d(100%, 0, 0)');
        $('#showTag').css('-webkit-transform', 'translate3d(100%, 0, 0)');
        $('#notes').css('-webkit-transform', 'translate3d(0, 0, 0)');
        setTimeout(function() {
          $('#addNote').hide();
          $('#showNote').hide();
          $('#editNote').hide();
          return $('#showTag').hide();
        }, 500);
      }
      return window.scrollNotes.refresh();
    },
    showNote: function(id) {
      show('#showNote', '#notes');
      show('#showNote', '#tagNote');
      this.note = window.homeNotes.get(id) || window.tagNotes.get(id);
      if (app.noteView) {
        app.noteView.close();
      }
      this.noteView = new NoteView({
        model: this.note
      });
      $('.detail').html(this.noteView.render().el);
      return window.scrollShowNote.refresh();
    }
  });

  show = function(self, main) {
    $(self).css('-webkit-transform', 'translate3d( 100%, 0, 0)');
    $(self).show();
    $(main).css('-webkit-transform', 'translate3d(-100%, 0, 0)');
    $(self).css('-webkit-transform', 'translate3d(0, 0, 0)');
    return setTimeout(function() {
      return $(main).hide();
    }, 500);
  };

  getNote = function(notesCollection, globalNoteList, apiurl, page, node, panel) {
    notesCollection.url = apiurl + page;
    return notesCollection.fetch({
      add: true,
      success: function() {
        if (!globalNoteList) {
          globalNoteList = new NoteListView({
            model: notesCollection
          });
        }
        if (page < 2) {
          $(node).append(globalNoteList.render().el);
          $(node).append('<div class="more" url="' + apiurl + '" page="' + page + '">加载更多</div><div class="loading">正在加载中……</div>');
        }
        $('.loading').hide();
        $('.more').show();
        $(panel).show();
        return localStorage.setItem('homeNotes', JSON.stringify(window.homeNotes));
      }
    });
  };

  $(function() {
    var inputHack, readFileAsDataURL;
    $('.btnlogin').click(function() {
      var login;
      login = new window.login();
      return login.login();
    });
    $('.more').live('click', function() {
      var page;
      page = Number($(this).attr('page')) + 1;
      $('.loading').show();
      $(this).hide();
      getNote(window.homeNotes, window.noteListView, './api/notes/', page, '.noteList', '#notes');
      return $(this).attr('page', page);
    });
    $('#create_note').click(function() {
      var noteData, noteModel;
      noteData = {
        title: $('#note_title').val(),
        content: $('#note_content').html(),
        tag: $('#note_tag').val()
      };
      noteModel = new NoteItem(noteData);
      window.homeNotes.url = './api/newnote/';
      window.isadd = true;
      return window.homeNotes.create(noteModel, {
        wait: true,
        success: function(model, response) {
          alert("添加成功");
          return window.app.navigate('home/', true);
        },
        error: function(model, error) {
          alert(error);
          return window.isadd = false;
        }
      });
    });
    $('#opEdit').click(function() {
      var id;
      $('#alertBox').hide();
      id = $(this).attr('nid');
      return window.app.navigate('edit/' + id, true);
    });
    $('#searchTag').click(function() {
      var tag;
      tag = $('#tagbox').val();
      return window.app.navigate('tag/' + tag, true);
    });
    $('#opDel').click(function() {
      var delnote, id;
      $('#alertBox').hide();
      if (confirm("您确定要删除该笔记?")) {
        id = $(this).attr('nid');
        delnote = window.homeNotes.get(id);
        delnote.urlRoot = './api/delnote/';
        return delnote.destroy({
          success: function() {
            return alert('该笔记已经成功删除');
          },
          error: function() {
            return alert('删除失败');
          }
        });
      }
    });
    $(".pickImg").live("change", function() {
      return readFileAsDataURL(this.files[0]);
    });
    readFileAsDataURL = function(file) {
      var reader;
      reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = function(event) {
        var img;
        img = $('<img  >');
        img.attr('src', event.target.result);
        return img.appendTo('.editor');
      };
      return reader.onerror = function() {
        return alert("读取该文件失败");
      };
    };
    window.scrollNotes = new iScroll('notes');
    window.scrollAddNote = new iScroll('addNote', {
      onBeforeScrollStart: inputHack
    });
    window.scrollShowNote = new iScroll('showNote');
    window.scrollEditNote = new iScroll('editNote', {
      onBeforeScrollStart: inputHack
    });
    window.scrollTagNote = new iScroll('tagNote');
    window.scrollShowTag = new iScroll('showTag', {
      onBeforeScrollStart: inputHack
    });
    inputHack = function(e) {
      var target;
      target = e.target;
      while (target.nodeType !== 1) {
        target = target.parentNode;
      }
      if (target.tagName !== 'SELECT' && target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') {
        return e.preventDefault();
      }
    };
    window.app = new AppRounter();
    return Backbone.history.start();
  });

}).call(this);