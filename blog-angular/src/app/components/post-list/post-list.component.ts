import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import {Router, ActivatedRoute, Params} from '@angular/router';

@Component({
  selector: 'post-list',
  templateUrl: './post-list.component.html',
  styleUrls: ['./post-list.component.css']
})
export class PostListComponent implements OnInit {
	
	
  @Input() posts;
	@Input() identity;
	@Input() url;

  @Output()
  eliminar = new EventEmitter<number>();

  constructor() { 
      
  }

  ngOnInit() {               
  }
  deleChat(postId){
    this.eliminar.emit(postId);

  }

}

