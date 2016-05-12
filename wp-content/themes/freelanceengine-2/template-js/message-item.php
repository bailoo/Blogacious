<?php
    if( defined( 'MILESTONE_DIR_URL' ) ) {
        ?>
        <script type="text/template" id="ae-message-loop">
            <# if( changelog == 1 ) { #>
                <span class="author-name">{{= author_name }}</span> marked <span class"">"{{= milestone_title}}"</span> as <span class="status">{{= action }}</span>
            <# } else { #>
               <div class="form-group-work-place">
        <!--        <a href="#" class="avatar-employer">-->
        <!--            {{=avatar}}-->
        <!--        </a>-->
                    <div class="content-chat-wrapper">
                        <div class="triangle"></div>
                        <div class="content-chat fixed-chat">
                         {{= comment_content }}
                         {{= file_list }}
                        </div>
                        <div class="date-chat">{{= message_time }}</div>
                    </div>
                </div>
            <# } #>
        </script>
        <?php
    } else {
        ?>
        <script type="text/template" id="ae-message-loop">
            <div class="form-group-work-place">
        <!--        <a href="#" class="avatar-employer">-->
        <!--            {{=avatar}}-->
        <!--        </a>-->
                    <div class="content-chat-wrapper">
                        <div class="triangle"></div>
                        <div class="content-chat fixed-chat">
                         {{= comment_content }}
                         {{= file_list }}
                        </div>
                        <div class="date-chat">{{= message_time }}</div>
                    </div>
                </div>
        </script>
        <?php
    }
?>
