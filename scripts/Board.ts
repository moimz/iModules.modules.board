/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판모듈 클래스를 정의한다.
 *
 * @file /modules/board/scripts/Board.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 15.
 */
namespace modules {
    export namespace board {
        export class Board extends Module {
            /**
             * 모듈의 DOM 이벤트를 초기화한다.
             *
             * @param {Dom} $dom - 모듈 DOM 객체
             */
            init($dom: Dom): void {
                if (Html.get('form[name=ModuleBoardWriteForm]', $dom).getEl() !== null) {
                    this.initWriteForm(Html.get('form[name=ModuleBoardWriteForm]', $dom));
                }

                super.init($dom);
            }

            /**
             * 게시물 작성폼을 초기화한다.
             *
             * @param {Dom} $form
             */
            initWriteForm($form: Dom): void {
                const form = Form.get($form);
                form.onSubmit(async (form) => {
                    const results = await form.submit(this.getProcessUrl('post'));
                    if (results.success == true) {
                        location.replace(iModules.getContextUrl('/view/' + results.post_id));
                    }
                });
            }
        }
    }
}
