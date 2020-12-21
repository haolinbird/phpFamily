namespace java com.jumei.show.rpc.service
namespace php Provider.IUserService



struct ResultModel {
  1: i32 code;
  2: string action;
  3: string data;
  4: string message;
}

service IUserService {
  ResultModel batchAddFans(1: string user_id, 2: list<string> attetion_uids);
  ResultModel batchFollowUser(1: string user_id, 2: list<string> attetion_uids);
}
